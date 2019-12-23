<?php
require_once BASEPATH . 'autoload.php';
echo userHTML( );

// cYear and cSemester are coming from controller.
$year = $cYear;
$semester = $cSemester;
$feedback = $cFeedback;

function getQuestionName( &$questionBank, $qid )
{
    return $questionBank[$qid];
}

function one2oneMappingWithQID( $feedback, &$questionBank )
{
    $response = [];
    $a = [];
    foreach( $feedback as $f )
        $a[$f['question_id']] = $f['response'];

    foreach( $questionBank as $qid => $q )
        $response[$qid] = __get__( $a, $qid, 'NA' );

    return $response;
}
?>

<!-- FORM to select year and semester -->
<?= selectYearSemesterForm( $year, $semester )?>

<?php
// Create a map out of feedback keys will be student id and course id.
$feedbackMap = array();
$instructorMap = array( );
$courseSpecificMap = array();
$countMap = array();
foreach($feedback as &$f)
{
    $f['login'] = crypt($f['login'], 'ArreyOhGabbar');
    $key = $f['course_id'].'@@'.$f['year'] .'@@'.$f['semester'];
    $feedbackMap[$key][$f['login']][] = $f;
    $countMap[$key][$f['question_id']][$f['instructor_email']][] = $f['response'];

    $inst = __get__( $f, 'instructor_email', '' );
    if($inst)
        $instructorMap[$inst] = $f;
    else
        $courseSpecificMap[$inst] = $f;
}

// Get question bank.
$questionBank = getQuestionsWithCategory( 'course feedback' );
$questionsById = array();
$questionChoices = [];
foreach( $questionBank as $cat => $qs )
{
    foreach( $qs as &$q )
    {
        $questionsById[$q['id']] = $q['question'];
        $questionChoices[$q['id']] = explode(',', __get__($q, 'choices', ''));
    }
}
ksort($questionsById);
ksort($feedbackMap);
ksort($countMap);

$options = explode(',', 'Strongly Agree,Agree,Neutral,Disagree,Strongly Disagree');

?>



<div class="h2">Feedback received for <?=count($feedbackMap)?> courses.</div>
<?php foreach($countMap as $key => $qidMap): ?>
    <div class="card">
        <div class="card-header h3">
            <?= getCourseName(explode('@@', $key)[0]) ?>
        </div>
        <div class="card-body">
            <table class="table text-sm exportable" id="<?=$key?>">
                <tr>
                    <th></th>
                    <th>Question</th>
                    <th>Instructor</th>
                    <th>Responses</th>
                <tr>
                <?php foreach($qidMap as $qid => $instMap):?>
                <?php foreach($instMap as $inst => $vals):?>
                <tr>
                    <td><?=$qid?></td>
                    <td><?=$questionsById[$qid]?></td>
                    <td><?=$inst?></td>
                    <?php $counter = array_count_values($vals) ?>
                    <td>
                    <table><tr>
                    <?php foreach($questionChoices[$qid] as $key): ?>
                    <th> <?=$key?> </th>
                    <?php endforeach; ?>
                    </tr><tr>
                    <?php foreach($questionChoices[$qid] as $key): ?>
                        <td> <?=__get__($counter, $key, 0)?></td>
                    <?php endforeach; ?>
                    </tr></table>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
<?php endforeach; ?>
<div class="card-body">
</div>
</div>
<?=goBackToPageLink( "$controller/home", "Go Home")?>

<script src="<?=base_url()?>./node_modules/xlsx/dist/xlsx.core.min.js"></script>
<script src="<?=base_url()?>./node_modules/file-saverjs/FileSaver.min.js"></script>
<script src="<?=base_url()?>./node_modules/tableexport/dist/js/tableexport.min.js"></script>
<script type="text/javascript" charset="utf-8">
TableExport(document.getElementsByClassName("exportable"));
</script>
