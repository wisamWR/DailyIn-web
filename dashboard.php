<?php
if (!defined('DAILYIN_ADMIN')) {
    http_response_code(403);
    exit;
}

//menghitung jumlah baris data article
$jumlah_article = (int) db_value("SELECT COUNT(*) FROM article");

//menghitung jumlah baris data gallery
$jumlah_gallery = (int) db_value("SELECT COUNT(*) FROM gallery");
?>
<div class="row row-cols-1 row-cols-md-4 g-4 justify-content-center pt-4">
    <div class="col">
        <div class="card border border-primary mb-3 shadow" style="max-width: 18rem;">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="p-3">
                        <h5 class="card-title"><i class="bi bi-newspaper"></i> Article</h5>
                    </div>
                    <div class="p-3">
                        <span class="badge rounded-pill text-bg-primary fs-2"><?php echo $jumlah_article; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card border border-primary mb-3 shadow" style="max-width: 18rem;">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div class="p-3">
                        <h5 class="card-title"><i class="bi bi-camera"></i> Gallery</h5>
                    </div>
                    <div class="p-3">
                        <span class="badge rounded-pill text-bg-primary fs-2"><?php echo $jumlah_gallery; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>