<?php  
defined('C5_EXECUTE') or die(_("Access Denied."));
class FormBlockController extends BlockController {

	public $btTable = 'btForm';
	public $btQuestionsTablename = 'btFormQuestions';
	public $btAnswerSetTablename = 'btFormAnswerSet';
	public $btAnswersTablename = 'btFormAnswers'; 	
	public $btInterfaceWidth = '420';
	public $btInterfaceHeight = '430';
	public $thankyouMsg=''; 
		
	/** 
	 * Used for localization. If we want to localize the name/description we have to include this
	 */
	public function getBlockTypeDescription() {
		return t("Build simple forms and surveys.");
	}
	
	public function getBlockTypeName() {
		return t("Form");
	}
	
	public function getJavaScriptStrings() {
		return array(
			'delete-question' => t('Are you sure you want to delete this question?'),
			'form-name' => t('Your form must have a name.'),
			'complete-required' => t('Please complete all required fields.'),
			'ajax-error' => t('AJAX Error.'),
			'form-min-1' => t('Please add at least one question to your form.')			
		);
	}
	
	public function __construct($b = null){ 
		parent::__construct($b);
		//$this->bID = intval($this->_bID);
		if(!strlen($this->thankyouMsg)){ 
			$this->thankyouMsg = $this->getDefaultThankYouMsg();
		}
	}
	
	public function getDefaultThankYouMsg() {
		return t("Thanks for taking the time to report a problem or ask a question. We're on it and you'll receive a response soon!");
	}
	
	//form add or edit submit
	function save( $data=array() ) {
		if( !$data || count($data)==0 ) $data=$_POST;
		$db = Loader::db();
		if(intval($this->bID)>0){	 
			$q = "select count(*) as total from {$this->btTable} where bID = ".intval($this->bID);
			$total = $db->getOne($q);
		}else $total = 0;
		$v = array( $data['qsID'], $data['surveyName'], intval($data['notifyMeOnSubmission']), $data['recipientEmail'], $data['thankyouMsg'], intval($this->bID) );
		
		$q = ($total > 0) ? "update {$this->btTable} set questionSetId = ?, surveyName=?, notifyMeOnSubmission=?, recipientEmail=?, thankyouMsg=? where bID = ?"
			: "insert into {$this->btTable} (questionSetId,surveyName, notifyMeOnSubmission, recipientEmail, thankyouMsg, bID) values (?, ?, ?, ?, ?, ?)";		

		$rs = $db->query($q,$v); 
		
		//Add Questions (for programmatically creating forms, such as during the site install)
		if( count($data['questions'])>0 ){
			$miniSurvey = new MiniSurvey();
			foreach( $data['questions'] as $questionData )
				$miniSurvey->addEditQuestion($questionData,0);
		} 

		return true;
	}
			
	function duplicate($newBID) {
		$db = Loader::db();
		$v = array($this->bID);
		$q = "select * from {$this->btTable} where bID = ? LIMIT 1";
		$r = $db->query($q, $v);
		$row = $r->fetchRow();
		if(count($row)>0){
			$oldQuestionSetId=$row['questionSetId'];
			$newQuestionSetId=time();
			//duplicate survey block record
			$v = array($newQuestionSetId,$row['surveyName'],$newBID);
			$q = "insert into {$this->btTable} (questionSetId,surveyName, , bID) values (?, ?, ?)";
			//duplicate questions records
			$rs=$db->query("SELECT * FROM {$this->btQuestionsTablename} WHERE questionSetId=$oldQuestionSetId");
			while( $row=$rs->fetchRow() ){
				$v=array($newQuestionSetId,$row['question'],$row['inputType'],$row['options'],$row['position']);
				$sql='INSERT INTO {$this->btQuestionsTablename} (questionSetId,question,inputType,options,position) VALUES (!,?,?,?,!)';
			}
		}
	}
	
	//users submits the completed survey
	function action_submit_form() {
		$txt = Loader::helper('text');
		$db = Loader::db();
		//question set id
		$qsID=intval($_POST['qsID']);	
		if($qsID==0)
			throw new Exception(t("Oops, something is wrong with the form you posted (it doesn't have a question set id)."));
		
		//save main survey record	
		$q="insert into {$this->btAnswerSetTablename} (questionSetId) values (?)";
		$db->query($q,array($qsID));
		$answerSetID=$db->Insert_ID();
		
		//get all questions for this question set
		$rs=$db->query("SELECT * FROM {$this->btQuestionsTablename} WHERE questionSetId=?", array($qsID));
		
		$questionAnswerPairs=array();
		
		//loop through each question and get the answers 
		while( $row=$rs->fetchRow() ){	
			//save each answer
			if($row['inputType']=='text'){
				$answerLong=$txt->sanitize($_POST['Question'.$row['msqID']]);
				$answer='';
			}else{
				$answerLong="";
				$answer=$txt->sanitize($_POST['Question'.$row['msqID']]);
			}
			
			$questionAnswerPairs[$row['msqID']]['question']=$row['question'];
			$questionAnswerPairs[$row['msqID']]['answer']=$txt->sanitize($_POST['Question'.$row['msqID']]);
			
			if( is_array($answer) ) 
				$answer=join(',',$answer);
			$v=array($row['msqID'],$answerSetID,$answer,$answerLong);
			$q="insert into {$this->btAnswersTablename} (msqID,asID,answer,answerLong) values (?,?,?,?)";
			$db->query($q,$v);
		}
		$refer_uri=$_POST['pURI'];
		if(!strstr($refer_uri,'?')) $refer_uri.='?';
		
		if(intval($this->notifyMeOnSubmission)>0){
			$mh = Loader::helper('mail');
			$mh->to( $this->recipientEmail ); 
			$mh->addParameter('formName', $this->surveyName);
			$mh->addParameter('questionSetId', $this->questionSetId);
			$mh->addParameter('questionAnswerPairs', $questionAnswerPairs); 
			$mh->load('block_form_submission');
			$mh->setSubject($this->surveyName.' '.t('Form Submission') ); 
			//echo $mh->body.'<br>';
			@$mh->sendMail(); 
		} 
		header("Location: ".$refer_uri."&surveySuccess=1&qsid=".$this->questionSetId);
		die;
	}		
	
	function delete() {
		$db = Loader::db();

		$miniSurvey=new MiniSurvey();
		$info=$miniSurvey->getMiniSurveyBlockInfo($this->bID);
		
		//get all answer sets
		$q = "SELECT asID FROM {$this->btAnswerSetTablename} WHERE questionSetId = ".intval($info['questionSetId']);
		$answerSetsRS = $db->query($q);
		
		//delete the answers
		while( $answerSet=$answerSetsRS->fetchRow() ){	 
			$q = "delete from {$this->btAnswersTablename} where asID = ".intval( $answerSet['asID'] );
			$r = $db->query($q);
		}
		 
		//delete the answer sets
		$q = "delete from {$this->btAnswerSetTablename} where questionSetId = ".intval($info['questionSetId']);
		$r = $db->query($q);
 
		//delete the questions
		$q = "delete from {$this->btQuestionsTablename} where questionSetId = ".intval($info['questionSetId']);
		$r = $db->query($q);	
		
		//delete the form block		
		$q = "delete from {$this->btTable} where bID = '{$this->bID}'";
		$r = $db->query($q); 				
	}
}

/**
 * Namespace for statistics-related functions used by the form block.
 *
 * @package Blocks
 * @subpackage BlockTypes
 * @author 
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */
class FormBlockStatistics {

	public static function getTotalSubmissions($date = null) {
		$db = Loader::db();
		if ($date != null) {
			return $db->GetOne("select count(asID) from btFormAnswerSet where DATE_FORMAT(created, '%Y-%m-%d') = ?", array($date));
		} else {
			return $db->GetOne("select count(asID) from btFormAnswerSet");
		}

	}
	
	public static function loadSurveys($MiniSurvey){
		$db = Loader::db();
		return $db->query('SELECT * FROM '.$MiniSurvey->btTable.' AS s');
	}
	
	public static $sortChoices=array('newest'=>'created DESC','chrono'=>'created');
	
	public static function buildAnswerSetsArray( $questionSet, $orderBy='', $limit='' ){
		$db = Loader::db();
		
		if( strlen(trim($limit))>0 && !strstr(strtolower($limit),'limit')  )
			$limit=' LIMIT '.$limit;
			
		if( strlen(trim($orderBy))>0 && array_key_exists($orderBy, self::$sortChoices) ){
			 $orderBySQL=self::$sortChoices[$orderBy];
		}else $orderBySQL=self::$sortChoices['newest'];
		
		//get answers sets
		$sql='SELECT * FROM btFormAnswerSet AS aSet '.
			 'WHERE aSet.questionSetId='.$questionSet.' ORDER BY '.$orderBySQL.' '.$limit;
		$answerSetsRS=$db->query($sql);
		//load answers into a nicer multi-dimensional array
		$answerSets=array();
		$answerSetIds=array(0);
		while( $answer = $answerSetsRS->fetchRow() ){
			//answer set id - question id
			$answerSets[$answer['asID']]=$answer;
			$answerSetIds[]=$answer['asID'];
		}		
		
		//get answers
		$sql='SELECT * FROM btFormAnswers AS a WHERE a.asID IN ('.join(',',$answerSetIds).')';
		$answersRS=$db->query($sql);
		
		//load answers into a nicer multi-dimensional array 
		while( $answer = $answersRS->fetchRow() ){
			//answer set id - question id
			$answerSets[$answer['asID']]['answers'][$answer['msqID']]=$answer;
		}
		return $answerSets;
	}
}

/**
 * Namespace for other functions used by the form block.
 *
 * @package Blocks
 * @subpackage BlockTypes
 * @author Tony Trupp <tony@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */
class MiniSurvey{

		public $btTable = 'btForm';
		public $btQuestionsTablename = 'btFormQuestions';
		public $btAnswerSetTablename = 'btFormAnswerSet';
		public $btAnswersTablename = 'btFormAnswers'; 	

		function MiniSurvey(){
			$db = Loader::db();
			$this->db=$db;
		}

		function addEditQuestion($values,$withOutput=1){
			$jsonVals=array();
			$values['options']=str_replace(array("\r","\n"),'%%',$values['options']); 
			
			//set question set id, or create a new one if none exists
			if(intval($values['qsID'])==0) $values['qsID']=time();
			
			//validation
			if( strlen($values['question'])==0 || strlen($values['inputType'])==0  || $values['inputType']=='null' ){
				//complete required fields
				$jsonVals['success']=0;
				$jsonVals['noRequired']=1;
			}else{
				if(intval($values['msqID'])>0){ 
					$dataValues=array(intval($values['qsID']), trim($values['question']), $values['inputType'],
								      $values['options'], intval($values['position']), intval($values['msqID']) );			
					$sql='UPDATE btFormQuestions SET questionSetId=?, question=?, inputType=?, options=?, position=? WHERE msqID=?';
					$jsonVals['mode']='"Edit"';
				}else{ 
					$dataValues=array( intval($values['qsID']), trim($values['question']), $values['inputType'],
								      $values['options'], 1000 );			
					$sql='INSERT INTO btFormQuestions (questionSetId,question,inputType,options,position) VALUES (?,?,?,?,?)';
					$jsonVals['mode']='"Add"';
				}
				$result=$this->db->query($sql,$dataValues); 
				$jsonVals['success']=1;
			}
			$jsonVals['qsID']=$values['qsID'];
			//create json response object
			$jsonPairs=array();
			foreach($jsonVals as $key=>$val) $jsonPairs[]=$key.':'.$val;
			if($withOutput) echo '{'.join(',',$jsonPairs).'}';
		}
		
		function getQuestionInfo($qsID,$msqID){
			$questionRS=$this->db->query('SELECT * FROM btFormQuestions WHERE questionSetId='.intval($qsID).' AND msqID='.intval($msqID).' LIMIT 1' );
			$questionRow=$questionRS->fetchRow();
			$jsonPairs=array();
			foreach($questionRow as $key=>$val){
				if($key=='options') $key='optionVals';
				$jsonPairs[]=$key.':"'.str_replace(array("\r","\n"),'%%',addslashes($val)).'"';
			}
			echo '{'.join(',',$jsonPairs).'}';
		}

		function deleteQuestion($qsID,$msqID){
			$sql='DELETE FROM btFormQuestions WHERE questionSetId='.intval($qsID).' AND msqID='.intval($msqID);
			$this->db->query($sql,$dataValues);
		} 
		
		static function loadQuestions($qsID){
			$db = Loader::db();
			return $db->query('SELECT * FROM btFormQuestions WHERE questionSetId='.intval($qsID).' ORDER BY position');
		}
		
		static function getAnswerCount($qsID){
			$db = Loader::db();
			return $db->getOne( 'SELECT count(*) FROM btFormAnswerSet WHERE questionSetId='.intval($qsID) );
		}		
		
		function loadSurvey($qsID,$showEdit=false){
			//loading questions	
			$questionsRS=self::loadQuestions($qsID);
		
			if(!$showEdit){
				echo '<table class="formBlockSurveyTable">';					
				while( $questionRow=$questionsRS->fetchRow() ){	
					echo '<tr>
							<td valign="top" class="question">'.$questionRow['question'].'</td>
							<td valign="top">'.$this->loadInputType($questionRow,$showEdit).'</td>';
					echo '</tr>';
				}			
				echo '<tr><td>&nbsp;</td><td><input class="formBlockSubmitButton" name="Submit" type="submit" value="'.t('Submit').'" /></td></tr>';
				echo '</table>';
			}else{
				echo '<div id="miniSurveyTableWrap"><div id="miniSurveyPreviewTable" class="miniSurveyTable">';					
				while( $questionRow=$questionsRS->fetchRow() ){	 ?>
					<div id="miniSurveyQuestionRow<?php  echo $questionRow['msqID']?>" class="miniSurveyQuestionRow">
						<div class="miniSurveyQuestion"><?php  echo $questionRow['question']?></div>
						<?php   /* <div class="miniSurveyResponse"><?php  echo $this->loadInputType($questionRow,$showEdit)?></div> */ ?>
						<div class="miniSurveyOptions">
							<div style="float:right">
								<a href="#" onclick="miniSurvey.moveUp(this,<?php  echo $questionRow['msqID']?>);return false" class="moveUpLink"></a> 
								<a href="#" onclick="miniSurvey.moveDown(this,<?php  echo $questionRow['msqID']?>);return false" class="moveDownLink"></a>						  
							</div>						
							<a href="#" onclick="miniSurvey.reloadQuestion(<?php  echo $questionRow['msqID']?>);return false"><?php  echo t('edit')?></a> &nbsp;&nbsp; 
							<a href="#" onclick="miniSurvey.deleteQuestion(this,<?php  echo $questionRow['msqID']?>);return false"><?php  echo t('remove')?></a>
						</div>
						<div class="miniSurveySpacer"></div>
					</div>
				<?php   }			 
				echo '</div></div>';
			}
		}
		
		function loadInputType($questionData,$showEdit){
			$options=explode('%%',$questionData['options']);				
			switch($questionData['inputType']){			
				case 'list': 
					// return 'what to do with lists?'; 				
					foreach($options as $option)
						$html.= '<option >'.trim($option).'</option>';
					return '<select name="Question'.$questionData['msqID'].'[]" size="4" multiple >'.$html.'</select>';							
			
				case 'select':
					if($this->frontEndMode)
						$html.= '<option value="">----</option>';					
					foreach($options as $option)
						$html.= '<option >'.trim($option).'</option>';
					return '<select name="Question'.$questionData['msqID'].'" >'.$html.'</select>';
								
				case 'radios':
					foreach($options as $option){
						if(strlen(trim($option))==0) continue;
						$html.= '<div class="radioPair"><input name="Question'.$questionData['msqID'].'" type="radio" value="'.trim($option).'" />&nbsp;'.$option.'</div>';
					}
					return $html;
					
				case 'text':
					return '<textarea name="Question'.$questionData['msqID'].'" cols="50" rows="4" style="width:95%"></textarea>';
					
				case 'field':
				default:
					return '<input name="Question'.$questionData['msqID'].'" type="field" />';
			}
		}
		
		function getMiniSurveyBlockInfo($bID){
			$rs=$this->db->query('SELECT * FROM btForm WHERE bID='.intval($bID).' LIMIT 1' );
			return $rs->fetchRow();
		}
		
		function reorderQuestions($qsID=0,$qIDs){
			$qIDs=explode(',',$qIDs);
			if(!is_array($qIDs)) $qIDs=array($qIDs);
			$positionNum=0;
			foreach($qIDs as $qID){
				$vals=array( $positionNum,intval($qID), intval($qsID) );
				$sql='UPDATE btFormQuestions SET position=? WHERE msqID=? AND questionSetId=?';
				$rs=$this->db->query($sql,$vals);
				$positionNum++;
			}
		}		
}	
?>