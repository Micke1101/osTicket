<?php
require('secure.inc.php');
if(!is_object($thisclient) || !$thisclient->isValid() || !$thisclient->canSeeOrgTickets()) die('Access denied'); //Double check again.

require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.json.php');
$ticket=null;
global $cfg;
if($_REQUEST['id'] && $_REQUEST['change']) {
    if (!($ticket = Ticket::lookup($_REQUEST['id']))) {
        $errors['err']=__('Unknown or invalid ticket ID.');
    } elseif(!$ticket->checkUserAccess($thisclient)) {
        $errors['err']=__('Unknown or invalid ticket ID.');
        $ticket=null;
    }
	if($ticket){
		if(strtolower($_REQUEST['change']) == 'approved'){
			$ticket->setStatusId(9);
		} elseif(strtolower($_REQUEST['change']) == 'denied'){
			$ticket->setStatusId(10);
		} else {
			$errors['err']=__('Invalid change.');
			$ticket=null;
		}
	}
}

$nav->setActiveNav('requests');
include(CLIENTINC_DIR.'header.inc.php');
include(CLIENTINC_DIR.'requests.inc.php');
include(CLIENTINC_DIR.'footer.inc.php');
?>