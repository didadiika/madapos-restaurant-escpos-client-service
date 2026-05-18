<?php

function imagePrint($photo_link){
    if (!empty($photo_link)) {
        $logoUrl = trim($photo_link);

        // Folder cache lokal
        $localDir = __DIR__ . '/../../images';

        if (!is_dir($localDir)) {
            mkdir($localDir, 0777, true);
        }

        // Ambil nama file dari URL
        $fileName = basename(parse_url($logoUrl, PHP_URL_PATH));
        $localPath = $localDir . DIRECTORY_SEPARATOR . $fileName;

        // Download jika file belum ada
        if (!file_exists($localPath)) {
            $ch = curl_init($logoUrl);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERAGENT => 'Mozilla/5.0',
            ]);

            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $curlError = curl_error($ch);

            curl_close($ch);

            if ($imageData === false) {
                throw new Exception("cURL error: {$curlError}");
            }

            if ($httpCode !== 200) {
                throw new Exception("HTTP {$httpCode} saat download logo.");
            }

            if (stripos($contentType, 'image/') !== 0) {
                throw new Exception(
                    "Response bukan gambar. Content-Type: {$contentType}"
                );
            }

            file_put_contents($localPath, $imageData);
        }

        // Cetak logo
        if (file_exists($localPath) && filesize($localPath) > 0) {
            try {
                $logo = EscposImage::load($localPath);

                $print->setJustification(Printer::JUSTIFY_CENTER);
                $print->graphics($logo);
                $print->feed(1);
            } catch (\Throwable $e) {
                error_log('Gagal mencetak logo: ' . $e->getMessage());
            }
        }
    }
}

?>
