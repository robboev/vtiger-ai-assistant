<?php
/*+**********************************************************************************
 * AI Assistant Event Handler
 * Registers a JS loader that injects the chat widget on every page
 ************************************************************************************/

class AIAssistantHandler extends VTEventHandler {

    public function handleEvent($eventName, $data) {
        // Not used — widget injection happens via footer include
    }
}
