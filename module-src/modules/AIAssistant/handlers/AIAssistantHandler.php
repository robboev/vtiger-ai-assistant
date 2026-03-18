<?php
/*+**********************************************************************************
 * AI Assistant Event Handler
 * Injects the chat widget into all vtiger pages
 ************************************************************************************/

class AIAssistantHandler extends VTEventHandler {

    public function handleEvent($eventName, $data) {
        // This handler is registered but the widget injection
        // happens via the Smarty template included in Header.tpl
        // or via the view postProcess method.
        // Kept for future event-driven hooks.
    }
}
