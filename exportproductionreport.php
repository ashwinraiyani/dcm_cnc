<?php
session_start();
include("inc/connection.php");

ini_set('max_execution_time', 6000);
ini_set('memory_limit', '-1');
date_default_timezone_set('Asia/Kolkata');
$today = date("Y-m-d");
$tabname = "production_1";
$tabpro1 = "production_2";
$tabpro2 = "production_3";
$tabpro3 = "production_machine_breakdown";
$tabmachine = "machine";
$taboperator = "operator";
$tabcustomer = "customer";
$title = "Manage Production Report";
$first_day = date('01/m/Y'); // hard-coded '01' for first day
$last_day = date('t/m/Y');
$pageUrl = $sitepath . "manageproductionreport.php";

if ($_SESSION["msdate"]) {
	$msdate = $_SESSION["msdate"];
}

if ($_SESSION["medate"]) {
	$medate = $_SESSION["medate"];
}
if ($msdate == "") {
	$msdate = $first_day;
}
if ($medate == "") {
	$medate = $last_day;
}

/// Check Login Session

if ($_SESSION["sadmin_username"] != "") {

	/** Error reporting */
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	ini_set('display_startup_errors', true);

	define('EOL', (PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

	date_default_timezone_set('Asia/kolkata');
	

	/** Include PHPExcel */
	require_once dirname(__FILE__) . '/Classes/Phpspreadsheet/vendor/autoload.php';

// Create new PHPExcel object
	$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

	$title = "Machine Report - " . $today;

// Set document properties
	//echo date('H:i:s') , " Set document properties" , EOL;

	$objPHPExcel->getProperties()->setCreator("DCM Industries")
		->setLastModifiedBy("DCM Industries")
		->setTitle($title)
		->setSubject($title)
		->setDescription("")
		->setKeywords("")
		->setCategory("");

	// Create a first sheet, representing sales data

	$objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load("sample/sampleproduction.xlsx");
	$objPHPExcel->setActiveSheetIndex(0);

	$k = 3;
	$j = 1;
	$i = 0;
	$styleArray = array(
		'font' => array(
			'name' => "Tahoma",
			'size' => 10,
		),
		'alignment' => array(
			'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
			'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
		),
		'borders' => array(
			'allborders' => array(
				'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
			),
		),

	);

	$wheresql = "";	
	if ($msdate != "" && $medate != "") {
		$date = DateTime::createFromFormat('d/m/Y', $msdate);
		$msdate = $date->format('Y-m-d');
		$date = DateTime::createFromFormat('d/m/Y', $medate);
		$medate = $date->format('Y-m-d');
		$wheresql = " where $tabname.productiondate >= '$msdate' and $tabname.productiondate <= '$medate'";
	}

	$ddate = "From :- " . $msdate . " To :- " . $medate;
	$k = 8;
	$sql = "SELECT $tabname.id,$tabname.productiondate,$tabmachine.machine,$taboperator.operator,$tabname.shift,$tabname.required_product_q_per_hr,$tabpro1.total_q_before_rejection,$tabpro2.total_q_after_rejection,$tabpro2.production_loss_increase_q,$tabpro3.total_breakdown_hours FROM $tabname LEFT JOIN $tabmachine ON $tabmachine.id=$tabname.machine LEFT JOIN $tabpro1 ON $tabpro1.production_1=$tabname.id LEFT JOIN $tabpro2 ON $tabpro2.production_1=$tabname.id LEFT JOIN $tabpro3 ON $tabpro3.production_1=$tabname.id LEFT JOIN $taboperator ON $taboperator.id=$tabname.operator".$wheresql;

	
	$rs = $db->query($sql) or die("cannot Select Customers" . $db->error);
	while ($row = $rs->fetch_assoc()) {
		$k++;
		$i++;
		$date = new DateTime($row["productiondate"]);
		$productiondate = $date->format('d-m-Y');

		if ($row["shift"] == '0') {
			$shift = "Day";
		} else {
			$shift = "Night";
		}

		$objPHPExcel->getActiveSheet()->setCellValue('A' . $k, $productiondate);
		$objPHPExcel->getActiveSheet()->setCellValue('B' . $k, $row["machine"]);
		$objPHPExcel->getActiveSheet()->setCellValue('C' . $k, $row["operator"]);
		$objPHPExcel->getActiveSheet()->setCellValue('D' . $k, $shift);
		$objPHPExcel->getActiveSheet()->setCellValue('E' . $k, $row["required_product_q_per_hr"]);
		$objPHPExcel->getActiveSheet()->setCellValue('F' . $k, $row["total_q_before_rejection"]);
		$objPHPExcel->getActiveSheet()->setCellValue('G' . $k, $row["total_q_after_rejection"]);
		$objPHPExcel->getActiveSheet()->setCellValue('H' . $k, $row["production_loss_increase_q"]);
		$objPHPExcel->getActiveSheet()->setCellValue('I' . $k, $row["total_breakdown_hours"]);

	}
	$objPHPExcel->getActiveSheet()->getStyle("A9:I" . $k)->applyFromArray($styleArray);

	
	// Setting Design
	$objPHPExcel->getActiveSheet()->getStyle("A9:I" . $k)->getFont()->setSize(8);
	$objPHPExcel->getActiveSheet()->getStyle("A9:I" . $k)->getFont()->setName('Calibri');
	$objPHPExcel->getActiveSheet()->getStyle("A9:I" . $k)->getAlignment()->setWrapText(true);

// Set page orientation and size
    //echo date('H:i:s') , " Set page orientation and size" , EOL;
	$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
	$objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

// Rename first worksheet
//    echo date('H:i:s') , " Rename first worksheet" , EOL;
	//   $objPHPExcel->getActiveSheet()->setTitle('FRESH');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
	$objPHPExcel->setActiveSheetIndex(0);
	/** Include PHPExcel_IOFactory */
	require_once dirname(__FILE__) . '/Classes/PHPExcel/IOFactory.php';

 
	// Save Excel 2007 file
	//date('H:i:s') , " Write to Excel2007 format" , EOL;
	$callStartTime = microtime(true);
	$filename = "export_production_report_from_" . $msdate . "_to_" . $medate . ".xlsx";
	$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
	$objWriter->save("export/" . $filename);
	$callEndTime = microtime(true);
	$callTime = $callEndTime - $callStartTime;
	$dir = $sitepath . "export/";
	header("Location: " . $dir . $filename . "");
	print "<META http-equiv='refresh' content=0; URL=" . $pageUrl . ">";
	exit;
} else {
	print "<META http-equiv='refresh' content=0;URL=index.php>";
}
?>