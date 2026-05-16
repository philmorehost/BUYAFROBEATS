<?php
namespace BAF;

class License {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;
    }

    public function generate_signed_license($sale_id) {
        $db = $this->core->db();
        $stmt = $db->prepare("SELECT s.*, b.title as beat_title 
                              FROM sales s 
                              JOIN beats b ON s.beat_id = b.id 
                              WHERE s.id = ?");
        $stmt->execute([$sale_id]);
        $sale = $stmt->fetch();

        if (!$sale) return null;

        $credit_phrase = $this->core->setting('license_credit', 'Produced by OBV');
        $signature = hash_hmac('sha256', $sale['delivery_id'], $this->core->setting('plisio_api_key', 'secret'));

        $html = "
        <div style='font-family:serif; max-width:800px; margin:40px auto; padding:60px; border:1px solid #eee; line-height:1.6;'>
            <h1 style='text-align:center; text-transform:uppercase; letter-spacing:4px;'>Exclusive License Agreement</h1>
            <p style='text-align:center; font-style:italic; color:#666;'>Reference ID: {$sale['delivery_id']}</p>
            
            <hr style='margin:40px 0; border:0; border-top:1px solid #000;'>
            
            <p>This Exclusive License Agreement is entered into on <b>" . date('F j, Y', strtotime($sale['created_at'])) . "</b> by and between the Producer (OBV) and the Licensee (<b>" . Core::escape($sale['winner_handle']) . "</b>).</p>
            
            <h3>1. THE COMPOSITION</h3>
            <p>The Producer hereby grants the Licensee an exclusive, perpetual license to use the musical composition titled: <b>\"" . Core::escape($sale['beat_title']) . "\"</b>.</p>
            
            <h3>2. RIGHTS GRANTED</h3>
            <p>Licensee is granted the exclusive right to reproduce, distribute, and perform the composition in all media formats worldwide. This includes unlimited streaming, radio airplay, and commercial performances.</p>
            
            <h3>3. CREDIT REQUIREMENTS</h3>
            <p>Licensee MUST include the following credit in the metadata of all releases: <b>\"{$credit_phrase}\"</b>. Failure to include this credit is a material breach of this agreement.</p>
            
            <h3>4. DELIVERY</h3>
            <p>The Master WAV and Stems are delivered via Google Drive. Access is guaranteed for 7 days from the date of this agreement.</p>
            
            <div style='margin-top:80px; display:flex; justify-content:space-between;'>
                <div>
                    <div style='border-bottom:1px solid #000; width:200px; height:40px;'></div>
                    <p>Producer: OBV</p>
                </div>
                <div>
                    <div style='border-bottom:1px solid #000; width:200px; height:40px; font-family:cursive; font-size:24px; text-align:center;'>" . Core::escape($sale['winner_handle']) . "</div>
                    <p>Licensee: " . Core::escape($sale['winner_handle']) . "</p>
                </div>
            </div>

            <div style='margin-top:60px; font-size:10px; color:#aaa; font-family:monospace; word-break:break-all;'>
                VERIFICATION HASH: {$signature}
            </div>
        </div>
        ";

        return $html;
    }
}
