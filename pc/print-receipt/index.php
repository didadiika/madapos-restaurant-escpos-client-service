<?php
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\ImagickEscposImage;#Butuh Ekstensi Imagick
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\RawbtPrintConnector;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

date_default_timezone_set("Asia/Jakarta");
require __DIR__ . '/../../helper/Tanggal_helper.php';
require __DIR__ . '/../../helper/Uang_helper.php';
require __DIR__ . '/../config/app.php';


$json = $_POST['json'];
$data = json_decode($json);
$jumlah_print = $_POST['jumlah_print'];

        

        /**JIKA ADA BILLS**/
        if($data->receipts){
            $pr_conn = array();
            $pr_usb = array();
            $pr_ip = array();
            $nump = 0;
            /**LOOPING ORDERS**/
            foreach($data->receipts as $receipt){
            $pr_conn[$nump] = $receipt->conn;
            $pr_usb[$nump] = $receipt->usb;
            $pr_ip[$nump] = $receipt->ip;
                /**INISIALIASASI SETTING KERTAS DAN ALIGMENT**/
                if(($receipt->paper == '58mm' && $receipt->type == '58') || ($receipt->paper == '80mm' && $receipt->type == '80'))
                {
                    if($receipt->paper == '58mm'){$lebar_pixel = 32;}else {$lebar_pixel = 48; }
                    $center = 'On';
                    $right = 'On';
                }
                else{
                    $lebar_pixel = 32;
                    $center = 'Off';
                    $right = 'Off';
                }
                /**INISIALIASASI SETTING KERTAS DAN ALIGMENT**/

                /**KONEKSI PRINTER**/
                
                if($nump == 0){
                    if($receipt->conn == "USB"){  
                        $connector = new WindowsPrintConnector($receipt->usb); 
                    } else if($receipt->conn == "Ethernet"){  
                        $connector = new NetworkPrintConnector($receipt->ip); 
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                    $printer = new Printer($connector);
                } else {
                    if($receipt->conn == "USB"){  
                        
                            $printer->close();
                            $connector = new WindowsPrintConnector($receipt->usb);
                            $printer = new Printer($connector);
                        
                    } else if($receipt->conn == "Ethernet"){  
                        if($pr_conn[$nump - 1] == "Ethernet" && $receipt->ip !=  $pr_ip[$nump - 1])
                        {
                            /** JIKA CONNECTOR SEBELUMNYA MENGGUNAKAN ETHERNET DAN IP YANG SAMA MAKA SETELAH DI CLOSE CONNECTOR TIDAK DAPAT
                             * DIBUKA LAGI (UNTUK KSWEB ANDROID) MAKA DIATASI DG SCRIPT INI **/
                            $printer->close();
                            $connector = new NetworkPrintConnector($receipt->ip);
                            $printer = new Printer($connector);
                        } 
                         
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                }

                /** JALANKAN PERINTAH PRINTER DISINI**/
                if($receipt->contents){
                    if($receipt->beep == "On")
                    {
                        $printer -> getPrintConnector() -> write(PRINTER::ESC . "B" . chr(4) . chr(1));
                    }    
                    
                    /** JUMLAH PRINT **/
                    for($i= 0; $i < $jumlah_print; $i++ ){
                        /** LOOPING MAKANAN **/
                        // $logo = EscposImage::load($images_path."/".$data->store->photo);
                        // if($center == 'On')
                        // {
                        // $printer -> setJustification(Printer::JUSTIFY_CENTER);
                        // }
                        // $printer->bitImage($logo);
                        // $printer -> feed();
                   
                    #Toko
                    if($center == 'On')
                    {
                        $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    $logo = EscposImage::load($data->print_setting->local_image_link);
                    if($center == 'On')
                    {
                    $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    $printer->bitImage($logo);
                    $printer->selectPrintMode(Printer::MODE_FONT_A);
                    $printer->setEmphasis(true);//berguna mempertebal huruf
                    $printer->text($data->store->header_bill."\n");
                    $printer->text($data->store->address."\n");
                    $printer->text($data->store->city."\n");
                    $printer->text($data->store->phone."\n");

                    $printer->selectPrintMode(Printer::MODE_FONT_A | Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
                    
                    if($data->customer->dine_type == "Dine In"){
                        $printer -> setTextSize(3, 2);
                        $printer -> text("#".$data->customer->numb_desk."\n");
                        $printer -> setTextSize(2, 1);
                        $printer -> text($data->customer->area."\n");
                    } else {
                        $printer -> text("#".$data->customer->dine_type."\n");
                    }
                    
                    $printer -> setTextSize(1, 1);
                    $printer->setEmphasis(true);//berguna mempertebal huruf
                   
                    if($center == 'On')
                    {
                    $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    $printer -> text(str_repeat('=',$lebar_pixel)."\n");
                    $printer -> setBarcodeHeight(40);
                    $printer -> setBarcodeWidth(2);
                    $printer->barcode($data->customer->sale_uid, Printer::BARCODE_CODE39);
                    $printer -> setJustification(Printer::JUSTIFY_LEFT);
                    $printer->text("UID      : ".$data->customer->sale_uid."\n");
                    $printer->text("Pelanggan: ".$data->customer->customer_name."\n");
                    $printer->text("Tanggal  : ".$data->customer->date."\n");
                    $printer->text("Kasir    : ".$data->customer->cashier."\n");
                    if($center == 'On')
                    {
                    $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    #Judul
                    $printer -> text(str_repeat('-', $lebar_pixel)."\n");
                    $batas = $lebar_pixel;
        
                    #Item
                    $no = 0;
                    $spasi_max_qty = 3;
                    $spasi_between_qty_items = 1;
                    $printer -> setJustification(Printer::JUSTIFY_LEFT);
                    if($receipt->paper == "80mm"){
                        $printer -> text("Qty Item".str_repeat(' ',13)."Price".str_repeat(' ',17)."Total\n");
                    } else if($receipt->paper == "58mm"){
                        $printer -> text("Qty Item".str_repeat(' ',7)."Price".str_repeat(' ',7)."Total\n");
                    }
                    $printer -> text(str_repeat('-', $lebar_pixel)."\n");
                    foreach($receipt->contents as $content){
                    $no++;
                        $nama_produk = ucwords(strtolower($content->name));#12
                        $qty = $content->qty;#1
                        $harga = uang($content->price);#6
                        $opr = " x ";#3
                        $sub_total = uang($total[] = $content->qty*$content->price);#6

                        $panjang_tengah = strlen($harga);
                        $sisa_batas = $batas - $panjang_tengah;
                        $sisa_batas_kiri = floor($sisa_batas/2);
                        $sisa_batas_kanan = ceil($sisa_batas/2) - strlen($sub_total);
                        
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        $printer -> text(str_repeat(' ',$spasi_max_qty - strlen($qty)).$qty.str_repeat(' ',$spasi_between_qty_items).$nama_produk."\n");
                        $printer -> text(str_repeat(' ', $sisa_batas_kiri).$harga.str_repeat(' ', $sisa_batas_kanan).$sub_total."\n");
                        }
                        $total = array_sum($total);
                        
                    
            $batas = 10;
            $panjang_total = strlen(uang($total));
            
            if($data->customer->disc_number > 0){
                $disc = "-".uang((int)$data->customer->disc_number);
                $grand_total = $total - (int)$data->customer->disc_number;
            } else if($data->customer->disc_percent > 0){
                $disc = "-".$data->customer->disc_percent."%";
                $grand_total = $total - ($total * (int)$data->customer->disc_percent/100);
            } else {
                $disc = "-";
                $grand_total = $total;
            }
            
            $panjang_discb = strlen($disc);
            $panjang_grand = strlen(uang($grand_total));
            $paid = (int)$data->customer->paid;
            $panjang_paid = strlen(uang($paid));
            $change = ((int)$data->customer->changed == 0) ? '-' : uang((int)$data->customer->changed);
            $panjang_change = strlen($change);
            $payment = $data->customer->payment;
            $panjang_payment = strlen($payment);


            if($receipt->type == '80'){
            $batas_kanan = 48 - $lebar_pixel;
            } else { $batas_kanan = 0; }
            $printer -> text(str_repeat('-', $lebar_pixel)."\n");
            $printer -> setJustification(Printer::JUSTIFY_RIGHT);
            $printer -> text("DISC     : ".str_repeat(' ', $batas - $panjang_discb + 2).$disc.str_repeat(' ', $batas_kanan)."\n");
            $printer -> text("TOTAL    : ".str_repeat(' ', $batas - $panjang_grand + 2).uang($grand_total).str_repeat(' ', $batas_kanan)."\n");
            $printer -> text("BAYAR    : ".str_repeat(' ', $batas - $panjang_paid + 2).uang($paid).str_repeat(' ', $batas_kanan)."\n");
            $printer -> text("KEMBALI  : ".str_repeat(' ', $batas - $panjang_change + 2).$change.str_repeat(' ', $batas_kanan)."\n");
            $printer -> text("PAYMENT  : ".str_repeat(' ', $batas - $panjang_payment + 2).$payment.str_repeat(' ', $batas_kanan)."\n");
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer -> text(str_repeat('=', $lebar_pixel)."\n");
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer->text($data->print_setting->printer_cashier_footer_info."\n");
            if($center == 'On')
            {
                $printer -> setJustification(Printer::JUSTIFY_CENTER);
            }
            $printer -> text("TERIMA KASIH \n");
            $mada_footer = EscposImage::load($images_path.'/'.$data->app_logo);
            $printer->bitImage($mada_footer);
            if($receipt->space_footer > 0){$printer -> feed($receipt->space_footer); }
            if($receipt->cutter == "On")
            {
                $printer->cut();#Memotong kertas
            }
                
                
            
            /** LOOPING MAKANAN **/
            }
            /** JUMLAH PRINT **/
                
            }
            
               
                /** JALANKAN PERINTAH PRINTER DISINI**/
            }
            $printer->close();
            /**LOOPING ORDERS**/
        }
        /**JIKA ADA BILLS**/

    echo "<script>window.close();</script>";
?>