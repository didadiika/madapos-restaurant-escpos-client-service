<?php
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;




$json = $_POST['json'];
$data = json_decode($json);
        
        /**JIKA ADA BILLS**/
        if($data->printers){

        $max_width = 48;# 48 char for 80mm & 32 for 58mm 
        $print_width_area = 576; # 576 dots for 80mm & 384 for 58mm 
        if($data->printers->printer_paper_size == '58mm'){
            $max_width = 32;# 48 char for 80mm & 32 for 58mm 
            $print_width_area = 384; # 576 dots for 80mm & 384 for 58mm 
        }
            
        switch($data->printers->printer_conn){
            case 'USB':
                $connector = new WindowsPrintConnector($data->printers->printer_address);
                break;
            case 'Ethernet':
                $connector = new NetworkPrintConnector($data->printers->printer_address);
                break;
            default:
                $connector = null;
        }
        if($connector){ #If Connector
            $print = new Printer($connector);#Open Koneksi Printer
            $print -> initialize();
            $print->setPrintWidth($print_width_area);
            $print -> setJustification(Printer::JUSTIFY_CENTER);
            $print->setEmphasis(true);
            $print->text("MadaPOS\n");
            $print->feed(1);
            if($data->printers->printer_conn == "USB"){
                $print->text("Connected with Windows USB Printing ".$data->printers->printer_address."\n");
            } else if($data->printers->printer_conn == "Ethernet"){
                $print->text("Connected with Ethernet on IP : ".$data->printers->printer_address."\n");
            }
            $print->feed(1);
            $print->text("MadaPOS\n");
            $print->feed();
            $print->cut();      
            $print->close();
        }
        }
        /**JIKA ADA BILLS**/
        echo "<script>window.close();</script>";
?>