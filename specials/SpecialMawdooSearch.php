<?php

// Special page: Allow user to search for any term.
class SpecialMawdooSearch extends SpecialPage {

    private $apiEndPoint = "https://www.googleapis.com/customsearch/v1";

	function __construct() {
		parent::__construct( 'MawdooSearch');
	}

	function execute( $par ) {

		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
		$this->addHelpLink( 'Help:Extension:MawdooSearch' );

		// Get term request parameter and replace any new line with space.
        $term = $this->getRequest()->getText('term');
        $term = str_replace( "\n", " ", $term );

        // Render html search form.
        $searchForm = $this->renderForm($term);

        // Add form to page output.
        $this->getOutput()->addHTML($searchForm);

        // Check if the operation is saved_data then run OnSaveResults hook [store selected links into d.b.].
        // Then redirect user to savedResults page
        if($request->getText('operation') == 'save_data')
        {
            Hooks::run('OnSaveResults',["search_result_item"=>$request->getArray('field_item')]);
            $redirect = SpecialPage::getTitleFor('SavedResults')->getLocalURL();
            $this->getOutput()->redirect($redirect);
            return;
        }

        // if term parameter is set and not empty, do the request from google custom search api.
        // Add api response to page output.
        if(isset($term) && $term != '')
        {
            $googleApiResponse = $this->googleSearchApi($term);
            $output->addHTML($googleApiResponse);
        }
	}

	// parameters: $term:string.
    // result: html content: search form.
	private function renderForm($term)
    {
        $output = Html::element('legend', ['class'=>'mawdoo-search-fieldset'],wfMessage('search'));
        $form = XML::openElement('div',['class'=>'mawdoo_search_container']).
                    XML::openElement('form',array('method' => 'get', 'action' => $this->getPageTitle()->getLocalURL(),'class'=>'mawdoo_search_form')).
                        XML::openElement('div',["class"=>'form-control text']).
                            XML::inputLabel(wfMessage('enter-your-keyword'),'term','term-textbox',false,$term).
                        XML::closeElement('div').
                        HTML::hidden('operation','search').
                        XML::openElement('div',["class"=>'form-control btn']).
                            XML::submitButton('Search',["class"=>'mawdoo-search-button']).
                        XML::closeElement('div').
                    XML::closeElement('form').
                XML::closeElement('div');

        $output = Xml::tags('fieldset', null, $output.$form);

        return $output;
    }

    // parameters: $term: string
    // return: formatted html response | html error message.
	private function googleSearchApi($term)
    {
        global $wgMawdooSearchGoogleApiKey,$wgMawdooSearchGoogleApiCx;
        try{
            // encode term
            $term= urlencode($term);
            // Get cURL resource
            $curl = curl_init();
            // set curl options: return & url
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => "$this->apiEndPoint?q=$term&key=$wgMawdooSearchGoogleApiKey&cx=$wgMawdooSearchGoogleApiCx&fields=items(title,link,snippet)",
            ));

            // Send the request & save response to $resp
            $resp = curl_exec($curl);
            // Close request
            curl_close($curl);

            $decodeApiResponse = json_decode($resp);
            // format api json response to html.
            $htmlApiResponse = $this->searchResultFormat($decodeApiResponse);
            return $htmlApiResponse;
        }
        catch (Exception $e)
        {
            return Xml::element('h3',null,wfMessage('something-wrong-here')).
                    Xml::element('p',null,wfMessage('technical-issues'));
        }
    }

    // params: data:stdObject. | data->items: array
    // return: html elements
    private function searchResultFormat($data)
    {
        $response =
            XML::openElement('div',['class'=>'search-results']).
            Xml::openElement('ul');

        try{
            // Check if data is set and $data->items is array and not null
            if(isset($data) && isset($data->items) && !is_null($data->items) && is_array($data->items)) {

                // Loop through all items and create html elements.
                foreach ($data->items as $index => $item) {

                    // field_item is an array that contains the following data
                    // - Html Checkbox: selected index
                    // - TextArea: comment text
                    // - Hidden field: title
                    // - Hidden field: desc
                    // - Hidden field: link
                    $textAreaFieldName = "field_item[$index]['comment']";
                    $checkBoxFieldName = "field_item[$index]['checked']";
                    $titleHiddenFieldName = Html::hidden("field_item[$index]['title']",$item->title);
                    $descHiddenFieldName = Html::hidden("field_item[$index]['desc']", $item->snippet);
                    $linkHiddenFieldName = Html::hidden("field_item[$index]['link']",$item->link);
                    $response .=
                        Xml::openElement('li') .
                        Xml::check(
                            $checkBoxFieldName,
                            false,
                            ['value' => $index, 'class' => 'checkbox-result-item']
                        ) .

                        XML::element('div', ['class' => 'search-result-item'], null) .
                        XML::element('h3', null, '') .
                        XML::element('a', ["href" => $item->link, "target" => "_blank"], $item->title) .
                        XML::closeElement('a') .
                        XML::closeElement('h3') .
                        XML::element('a', ["href" => $item->link, "target" => "_blank"], $item->link) . XML::closeElement('a') .
                        XML::element('p', null, $item->snippet).

                        XML::openElement('div',['class'=>'form-textarea']).
                            XML::label(wfMessage('enter-your-comment'), $textAreaFieldName) .
                            XML::element('small',['class'=>'comment-hint'],' ( '.wfMessage('check-checkbox').' ) ').
                        XML::closeElement('div').

                        XML::textarea($textAreaFieldName, '',40,5,['class'=>"comment-text-area",'disabled'=>'disabled']) .
                        $titleHiddenFieldName.
                        $descHiddenFieldName.
                        $linkHiddenFieldName.
                        XML::closeElement('div') .
                        Xml::closeElement('li');
                }
                $response .= Xml::closeElement('ul');
                $response .= XML::closeElement('div');

                // Add operation parameter with save_data value.
                $response.= Xml::openElement('div',null).
                                Html::hidden('operation', 'save_data').
                                XML::element('button',['type'=>'submit','class'=>'save-data'], !$this->getUser()->isAllowed( 'execute' )?wfMessage('login-to-save'):wfMessage('save')).
                            Xml::closeElement('div');
                $response = Xml::tags('form', array('method' => 'post', 'action' => $this->getPageTitle()->getLocalURL()), $response);

                return $response;
            }
            else if(isset($data) && isset($data->error) && !empty($data->error->errors))
            {
                return Xml::element('h3',null,wfMessage('something-wrong-here')).
                    Xml::element('p',null,wfMessage('technical-issues'));
            }
            else
            {
                return XML::element('h2',null,wfMessage('no-results')).
                    Xml::element('p',null,wfMessage('no-results-body'));
            }
        }
        catch (Exception $exception)
        {
            return Xml::element('h3',null,wfMessage('something-wrong-here')).
                Xml::element('p',null,wfMessage('technical-issues'));
        }

    }

    // Set a group name for this page.
    protected function getGroupName() {
        return 'mawdoo3_group';
    }


}
