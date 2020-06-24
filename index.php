<?php 
session_name("gmk-pdfsplitter");
session_start();
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(1);
include "splitter.php";
$message="";
$message1="";
$filepath=substr($_SERVER['SCRIPT_FILENAME'],0,strripos($_SERVER['SCRIPT_FILENAME'],"/")+1);
$usePro=date("U")<strtotime("12 February 2015")?1:0;
$folders=array();
$folders['in']="in/".session_id();
$folders['outmain']="out/".session_id();
if(isset($_POST['split']))
{
	if($_FILES['original']['error']==0)
	{
		$fname=str_ireplace(".pdf","",$_FILES['original']['name']);
		$filename = $fname.".zip";
		$folders['out']=$folders['outmain']."/".$fname;
		$folders['zip']=$folders['out']."/tozip/";
		
		/* set up folders */
		foreach($folders as $folder)
		{
			if(!is_dir($folder)){mkdir($folder,0777);}//make folder
			$oldfiles=glob($folder."/*");
			foreach($oldfiles as $oldfile){@unlink($oldfile);/*remove anything left over incase it causes probs*/}
		}
	
		move_uploaded_file($_FILES['original']['tmp_name'],$folders['in']."/".$_FILES['original']['name']);
	
		try
		{
			if($usePro)
			{
				$split=split_pdf_pro($folders['in']."/".$_FILES['original']['name'], $folders['out'].'/',$_FILES['original']['name']);
			}
			else
			{
				$split=split_pdf($folders['in']."/".$_FILES['original']['name'], $folders['out'].'/',$_FILES['original']['name']);
			}
			if($split)
			{$newfiles=glob($folders['out']."/*.[pP][dD][fF]");		
			$result = makezip($folders['zip'],$newfiles,$folders['out']."/".$filename);//zip for easy download ($reult returns 0 or 1)
			if($result)
			{
				/* Load zip for downloading */		
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: public");
				header("Content-Description: File Transfer");
				header("Content-type: application/octet-stream");
				header("Content-Disposition: attachment; filename=\"".$filename."\"");
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: ".filesize($filepath.$folders['out']."/".$filename));
				ob_end_flush();
				@readfile($filepath.$folders['out']."/".$filename);
			}
			else
			{
				$message="Error while zipping";
			}}else{
			$message="Error while splitting";}
		}
		catch(Exception $e)
		{
			$message="Error while splitting";
			$excep=$e->getMessage();
			if(strlen($excep)>0){$message=$excep;}
		}
	
		remdir($folders['in']);//remove uploaded pdfs
		remdir($folders['outmain']);//remove output folders
	}else{
		$message="Please select a file to split.";
	}
}
else if(isset($_POST['merge']))
{
	if(array_search(0,$_FILES['original']['error'])!==false)
	{
		$folders['pages']=$folders['in']."/".$outname."_pages";//store uploaded pdfs ready for merging
		//$folders['zip']=$folders['out']."/tozip/";	
		/* set up folders */
		foreach($folders as $folder)
		{
			if(!is_dir($folder)){mkdir($folder,0777);}//make folder
			$oldfiles=glob($folder."/*");
			foreach($oldfiles as $oldfile){@unlink($oldfile);/*remove anything left over incase it causes probs*/}
		}
		/* move pdfs */
		$mfiles=array();
		foreach($_FILES['original']['name'] as $n => $name)
		{
			if($_FILES['original']['error'][$n]==0)
			{
				move_uploaded_file($_FILES['original']['tmp_name'][$n],$folders['pages']."/".$name);
				$mfiles[]=$folders['pages']."/".$name;
			}
		}
		try
		{
			$outname=strlen(trim($_POST['newname']))>0?$_POST['newname']:str_ireplace(".pdf","",basename($mfiles[0]));//take name from first file if none set
			merge_pdf($mfiles, $folders['outmain'].'/',$outname);
			/* Offer pdf for downloading */		
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: public");
			header("Content-Description: File Transfer");
			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"".$outname.".pdf\"");
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".filesize($filepath.$folders['outmain'].'/'.$outname.".pdf"));
			ob_end_flush();
			@readfile($filepath.$folders['outmain'].'/'.$outname.".pdf");
		}
		catch(Exception $e)
		{
			$message1="Error while merging";
			$excep=$e->getMessage();
			if(strlen($excep)>0){$message1=$excep;}
		}
		remdir($folders['in']);//remove uploaded pdfs
		remdir($folders['outmain']);//remove output folders
	}else{
		$message1="Please select files to merge.";
	}
}
?>
<!doctype html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<link href="style.css" rel="stylesheet" type="text/css" />
<meta charset="utf-8">
<title>GMK PDF Tools</title>
</head>

<body>
<!--<div id="loading" style="display:none;"><div>Working...<br /><img src="loadingbar.gif" alt="" /></div></div>-->
<div id="header">
	<div style="float:left;"><img src="logo.jpg" alt="<?=$sitename?>" style="margin:10px;" /></div>
	
	<div class="clear"></div>
</div>

<div id="sitecontent">

<div style="text-align:center;padding-top:20px;">
<h1>PDF Splitter</h1>
<form action="index.php" method="post" enctype="multipart/form-data" style="padding:5px;background:#FFF;border:1px solid #91BDE4;width:400px;margin:auto">
File to split: <input type="file" name="original" accept="application/pdf" /><br />
<input type="submit" name="split" value="Split" />
</form>
<?=$usePro?"":"<dfn>*Daily Invoices LLC &amp; Invoices for PO# Don't work without a <a href='https://www.setasign.com/products/fpdi-pdf-parser/pricing/#p-163'>licence</a></dfn>"?>
<? if(strlen($message)>0){	?><div style="width:400px;margin:10px auto 0;text-align:left;border:1px solid red;background:#f4f4f4;padding:5px;"><strong>ERROR:</strong><br /><?=str_ireplace(str_replace("/","\\",$filepath.$folders['in'])."\\","",$message)?></div><? }?>
</div>

<div style="text-align:center;padding-top:20px;">
<h1>PDF Merge</h1>
<form action="index.php" method="post" name="mergeform" enctype="multipart/form-data" style="padding:5px;background:#FFF;border:1px solid #91BDE4;width:400px;margin:auto">
Output PDF name: <input type="text" name="newname" value="" autofocus />
<div id="filefields">
<label for="file1">Page 1: </label><input type="file" id="file1" name="original[]" onchange="filePicked(this)" multiple accept="application/pdf" /><span id="file1_del"></span><br />
<label for="file2">Page 2: </label><input type="file" id="file2" name="original[]" onchange="filePicked(this)" multiple accept="application/pdf" /><span id="file2_del"></span><br />
<label for="file3">Page 3: </label><input type="file" id="file3" name="original[]" onchange="filePicked(this)" multiple accept="application/pdf" /><span id="file3_del"></span><br />
<label for="file4">Page 4: </label><input type="file" id="file4" name="original[]" onchange="filePicked(this)" multiple accept="application/pdf" /><span id="file4_del"></span><br />
<label for="file5">Page 5: </label><input type="file" id="file5" name="original[]" onchange="filePicked(this)" multiple accept="application/pdf" /><span id="file5_del"></span>
</div>
<input type="button" onclick="addFields(5);" value="Add 5" /><input type="submit" name="merge" value="Merge" />
</form>
<?=$usePro?"":"<dfn>*Daily Invoices LLC &amp; Invoices for PO# Don't work without a <a href='https://www.setasign.com/products/fpdi-pdf-parser/pricing/#p-163'>licence</a></dfn>"?>
<? if(strlen($message1)>0){	?><div style="width:400px;margin:10px auto 0;text-align:left;border:1px solid red;background:#f4f4f4;padding:5px;"><strong>ERROR:</strong><br /><?=str_ireplace(str_replace("/","\\",$filepath.$folders['pages'])."\\","",$message1)?></div><? }?>
</div>

</div>
<script type="text/javascript">
function addFields(num)
{
	fDiv=document.getElementById('filefields');
	curNum=document.forms['mergeform'].elements["original[]"].length;
	for(xx=1;xx<num+1;xx++)
	{
		thisNum=(curNum+xx);
		newBr=document.createElement('br');
		newLab=document.createElement('label');	newLab.setAttribute('for','file'+thisNum); newLab.innerHTML='Page '+thisNum+': ';
		newInp=document.createElement('input');	newInp.setAttribute('id','file'+thisNum);	newInp.setAttribute('type','file');newInp.setAttribute('name','original[]');newInp.setAttribute('onchange','filePicked(this)');newInp.setAttribute('multiple','multiple');newInp.setAttribute('accept','application/pdf');
		newSpan=document.createElement('span');	newSpan.setAttribute('id','file'+thisNum+'_del');
		fDiv.appendChild(newBr);
		fDiv.appendChild(newLab);
		fDiv.appendChild(newInp);
		fDiv.appendChild(newSpan);
	}
}
function filePicked(obj)
{
	theSpan=document.getElementById(obj.id+'_del');
	theSpan.setAttribute('onclick','clearField(this)');
	theSpan.innerHTML="&#10006;";
}
function clearField(obj)
{
	fieldId=obj.id.replace('_del','');
	document.getElementById(fieldId).value="";
	obj.removeAttribute('onclick');
	obj.innerHTML="";
}
</script>
</body>
</html>