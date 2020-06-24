<?php
session_name("gmk-pdfsplitter");
function split_pdf_pro($afilename, $end_directory = false, $outputfname = "")
{
	require('fpdiTest/loadLicense.php');
	require_once('fpdf/fpdf.php');
	require_once('fpdiTest/fpdi.php');
	
	$end_directory = $end_directory ? $end_directory : './';
	$outfile=strlen($outputfname)>0?$outputfname:basename($afilename);

	$pdf = new FPDI();
	$pagecount = $pdf->setSourceFile($afilename); // How many pages?
	// Split each page into a new PDF
	$err=0;
	for ($i = 1; $i <= $pagecount; $i++)
	{
		$new_pdf = new FPDI();
		$new_pdf->SetTitle(str_ireplace('.pdf', '', $outfile)." Page ".$i);
		$new_pdf->SetCreator("GMK PDF Tools");
		$new_pdf->AddPage();
		$new_pdf->setSourceFile($afilename);
		$new_pdf->useTemplate($new_pdf->importPage($i), null, null, 0, 0, true);
		try
		{
			$new_filename = $end_directory.str_ireplace('.pdf', '', $outfile).'_'.$i.".pdf";
			$new_pdf->Output($new_filename, "F");
		}
		catch (Exception $e)
		{
			return 0;
			$err=1;
		}
	}
	$pdf->close();

	if($err!=1){return $pagecount;}
}
function split_pdf($afilename, $end_directory = false, $outputfname = "")
{
	require_once('fpdf/fpdf.php');
	require_once('fpdi/fpdi.php');
	
	$end_directory = $end_directory ? $end_directory : './';
	$outfile=strlen($outputfname)>0?$outputfname:basename($afilename);

	$pdf = new FPDI();
	$pagecount = $pdf->setSourceFile($afilename); // How many pages?
	// Split each page into a new PDF
	$err=0;
	for ($i = 1; $i <= $pagecount; $i++)
	{
		$new_pdf = new FPDI();
		$new_pdf->SetTitle(str_ireplace('.pdf', '', $outfile)." Page ".$i);
		$new_pdf->SetCreator("GMK PDF Tools");
		$new_pdf->AddPage();
		$new_pdf->setSourceFile($afilename);
		$new_pdf->useTemplate($new_pdf->importPage($i));
		try
		{
			$new_filename = $end_directory.str_ireplace('.pdf', '', $outfile).'_'.$i.".pdf";
			$new_pdf->Output($new_filename, "F");
		}
		catch (Exception $e)
		{
			return 0;
			$err=1;
		}
	}
	$pdf->close();

	if($err!=1){return $pagecount;}
}
function merge_pdf($files, $end_directory = false, $outputfname = "")
{
	require_once('fpdf/fpdf.php');
	require_once('fpdi/fpdi.php');
	
	$end_directory = $end_directory ? $end_directory : './';
	
	$err=0;
	// $files=glob($folder."/*.[pP][dD][fF]");
	$outfile=strlen($outputfname)>0?$outputfname:basename($files[0]);
	$new_pdf = new FPDI();//start the file to add pages to
	$new_pdf->SetTitle($outfile);
	$new_pdf->SetCreator("GMK PDF Tools");
	//$new_pdf->SetAuthor($title);
	foreach ($files as $i => $filename)
	{
		$pdf = new FPDI();//open a file to read from
		$pagecount=$pdf->setSourceFile($files[$i]);
		for ($p = 1; $p <= $pagecount; $p++)
		{
			$new_pdf->AddPage();//start a new page
			$new_pdf->setSourceFile($files[$i]);//read full pdf
			$new_pdf->useTemplate($new_pdf->importPage($p), null, null, 0, 0, true);//read in page from full pdf
		}
	}
	try
	{
		$new_filename = $end_directory.$outfile.".pdf";
		$new_pdf->Output($new_filename, "F");
	}
	catch (Exception $e)
	{
		return 0;
		$err=1;
	}
	$pdf->close();

	if($err!=1){return $pagecount;}
}
function makezip($zipfolder,$postfiles,$dest)
{
	foreach($postfiles as $tmpfile)
	{
		//$tmpfile=str_replace(" ","%20",$tmpfile);
		copy($tmpfile,$zipfolder.basename($tmpfile));
	}
	
	$files=glob($zipfolder."*");
	if(count($files)>0)
	{
		$zip = new ZipArchive();
		if($zip->open($dest,true ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			return false;
		}
		foreach($files as $file) {
			$zip->addFile($file,basename($file));
		}
		//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
		$zip->close();
		return file_exists($dest);
	}
	else
	{
		return 0;
	}	
}
function remdir($dir)
{
	$files=glob($dir."/*");
	foreach($files as $file)
	{
		if(is_dir($file)) { 
			remdir($file);
		} else {
			unlink($file);
		}
	}
	rmdir($dir);
}
?>
