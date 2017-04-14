<?php
if(!defined('OSTCLIENTINC') || !is_object($thisclient) || !$thisclient->isValid() || !$thisclient->canSeeOrgTickets()) die('Access Denied');

$tickets = Ticket::objects();

$qs = array();
$status=null;

$sortOptions=array('id'=>'number', 'subject'=>'cdata__subject',
                    'status'=>'status__name', 'dept'=>'dept__name','date'=>'created');

$basic_filter = Ticket::objects();
$basic_filter->filter(array('status' => $cfg->getDefaultStatusWaiting()));

$visibility = $basic_filter->copy()
    ->values_flat('ticket_id')
    ->filter(array('user_id' => $thisclient->getId()))
    ->union($basic_filter->copy()
        ->values_flat('ticket_id')
        ->filter(array('thread__collaborators__user_id' => $thisclient->getId()))
    , false);

$visibility = $visibility->union(
    $basic_filter->copy()->values_flat('ticket_id')
        ->filter(array('user__org_id' => $thisclient->getOrgId()))
, false);

$tickets->distinct('ticket_id');

$total=$visibility->count();
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$pageNav->setURL('requests.php', $qs);
$tickets->filter(array('ticket_id__in' => $visibility));
$pageNav->paginate($tickets);

$showing =$total ? $pageNav->showing() : "";
if(!$results_type)
{
    $results_type=ucfirst($status).' '.__('Requests');
}
$showing.=($status)?(' '.$results_type):' '.__('All Requests');

$tickets->values(
    'ticket_id', 'number', 'created', 'isanswered', 'source', 'status_id',
    'status__state', 'status__name', 'cdata__subject', 'dept_id',
    'dept__name', 'dept__ispublic', 'user__name'
);

?>

<h1 style="margin:10px 0">
    <a href="<?php echo Format::htmlchars($_SERVER['REQUEST_URI']); ?>"
        ><i class="refresh icon-refresh"></i>
    <?php echo __('Requests'); ?>
    </a>
</h1>
<table id="ticketTable" width="800" border="0" cellspacing="0" cellpadding="0">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th nowrap>
                <?php echo __('Ticket #');?>
            </th>
            <th width="100">
                <?php echo __('Create Date');?>
            </th>
            <th width="120">
                <?php echo __('User');?>
            </th>
            <th width="300">
                <?php echo __('Subject');?>
            </th>
            <th width="70">
                <?php echo __('Approve');?>
            </th>
            <th width="70">
                <?php echo __('Deny');?>
            </th>
        </tr>
    </thead>
    <tbody>
    <?php
     $subject_field = TicketForm::objects()->one()->getField('subject');
     if ($tickets->exists(true)) {
         foreach ($tickets as $T) {
            $subject = $subject_field->display(
                $subject_field->to_php($T['cdata__subject']) ?: $T['cdata__subject']
            );
            $user = $T['user__name'];
            if (false) // XXX: Reimplement attachment count support
                $subject.='  &nbsp;&nbsp;<span class="Icon file"></span>';

            $ticketNumber=$T['number'];
            if($T['isanswered'] && !strcasecmp($T['status__state'], 'open')) {
                $subject="<b>$subject</b>";
                $ticketNumber="<b>$ticketNumber</b>";
            }
            ?>
            <tr id="<?php echo $T['ticket_id']; ?>">
                <td>
                <a class="Icon <?php echo strtolower($T['source']); ?>Ticket"
                    href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><?php echo $ticketNumber; ?></a>
                </td>
                <td><?php echo Format::date($T['created']); ?></td>
                <td><?php echo $user; ?></td>
                <td>
                    <div style="max-height: 1.2em; max-width: 320px;" class="link truncate" href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><?php echo $subject; ?></div>
                </td>
                <td><a href="requests.php?id=<?php echo $T['ticket_id']; ?>&change=approved">Approve</a></td>
                <td><a href="requests.php?id=<?php echo $T['ticket_id']; ?>&change=denied">Deny</a></td>
            </tr>
        <?php
        }

     } else {
         echo '<tr><td colspan="6">'.__('Your query did not match any records').'</td></tr>';
     }
    ?>
    </tbody>
</table>
<?php
if ($total) {
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
}
?>
