<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
?>
<?php
/*
 * ============================================
 * 메인 페이지 index.php (안전 버전)
 * ============================================
 */

define('CONFIG_TABLE', 'dm_config');
define('BANNER_TABLE', 'mainbanner');
define('POPUP_TABLE', 'dm_popup');
define('BOARD_PREFIX', 'g5_write_');
define('REVIEW_BOARD', 'review');
define('CASE_BOARD', 'case');
define('COLUMN_BOARD', 'column');

include_once('./_common.php');

// ============================================
// 사이트 설정 안전하게 가져오기
// ============================================
function get_default_config()
{
    return [
        // SEO
        'cf_title'           => '',
        'cf_description'     => '',
        'cf_og_image'        => '',
        'cf_favicon'         => '',
        'cf_logo'            => '',
        'cf_site_name'       => '',
        'cf_ga_id'           => '',
        'cf_add_script'      => '',
        'cf_add_body_script' => '',
        // 회사 정보
        'cf_tel'             => '',
        'cf_address'         => '',
        'cf_city'            => '',
        'cf_lat'             => '',
        'cf_lng'             => '',
        'cf_company_name'    => '',
        'cf_ceo_name'        => '',
        'cf_business_number' => '',
        // SNS
        'cf_blog'            => '',
        'cf_kakao'           => '',
        'cf_naver_booking'   => '',
        'cf_naver_talk'      => '',
        'cf_instagram'       => '',
        'cf_youtube'         => '',
        // 진료시간
        'cf_open_time'       => '',
        'cf_close_time'      => '',
        'cf_sat_open'        => '',
        'cf_sat_close'       => '',
        'cf_night_days'      => '',
        'cf_night_open'      => '',
        'cf_night_close'     => '',
        'cf_lunch_open'      => '',
        'cf_lunch_close'     => '',
        // 법적
        'cf_privacy_policy'  => '',
        'cf_terms'           => '',
    ];
}

function safe_get_config()
{
    $table_check = sql_query("SHOW TABLES LIKE 'dm_config'");
    if (sql_num_rows($table_check) == 0) {
        error_log("ERROR: dm_config 테이블이 존재하지 않습니다!");
        return get_default_config();
    }

    $config = @sql_fetch("SELECT * FROM dm_config WHERE cf_id = 1");

    if (!$config) {
        error_log("WARNING: dm_config에 cf_id=1 레코드가 없습니다. 자동 생성합니다.");
        sql_query("INSERT INTO dm_config (cf_id) VALUES (1)");
        $config = sql_fetch("SELECT * FROM dm_config WHERE cf_id = 1");
    }

    $defaults = get_default_config();
    foreach ($defaults as $key => $value) {
        if (!isset($config[$key]) || $config[$key] === null) {
            $config[$key] = $value;
        }
    }

    return $config;
}

$site_config = safe_get_config();

// ============================================
// 배너 설정 가져오기
// ============================================
$banner_row = sql_fetch("SELECT * FROM mainbanner WHERE is_main = 1");
if (!$banner_row) {
    $banner_row = sql_fetch("SELECT * FROM mainbanner ORDER BY id ASC LIMIT 1");
}

$banner_images = [];
$banner_settings = [
    'autoplay_delay' => 3000,
    'effect'         => 'slide',
    'loop'           => 1,
    'speed'          => 600
];

if ($banner_row) {
    $banner_images = $banner_row['images'] ? json_decode($banner_row['images'], true) : [];
    if ($banner_row['settings']) {
        $banner_settings = json_decode($banner_row['settings'], true);
    }
}

if (empty($banner_images)) {
    $banner_images = [[
        'src'           => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1920',
        'visible'       => true,
        'link'          => '',
        'text_settings' => [
            'title'         => 'Welcome to Our Site',
            'description'   => 'Please add banners in admin panel',
            'button_text'   => '',
            'button_link'   => '',
            'text_color'    => '#ffffff',
            'text_position' => 'center'
        ]
    ]];
}

// ============================================
// 슬라이드 데이터 가져오기
// ============================================
$slide_tabs = [];
$r = sql_query("SELECT DISTINCT tab FROM dm_slide WHERE is_visible=1 AND tab!='' ORDER BY tab ASC");
while ($row = sql_fetch_array($r)) $slide_tabs[] = $row['tab'];

$slide_by_tab = [];
$r_all = sql_query("SELECT * FROM dm_slide WHERE is_visible=1 ORDER BY display_order ASC, id ASC");
while ($row = sql_fetch_array($r_all)) $slide_by_tab[$row['tab'] ?: '기타'][] = $row;

$slide_result = sql_query("SELECT * FROM dm_slide WHERE is_visible = 1 ORDER BY tab ASC, display_order ASC, id ASC");
$slides = [];
while ($row = sql_fetch_array($slide_result)) $slides[] = $row;

$tabs = [];
foreach ($slides as $s) {
    if ($s['tab'] && !in_array($s['tab'], $tabs)) $tabs[] = $s['tab'];
}
$grouped = [];
foreach ($slides as $s) {
    $grouped[$s['tab'] ?: '__none__'][] = $s;
}
$render_tabs = !empty($tabs) ? $tabs : ['__none__'];

// ============================================
// 안전 SQL 함수
// ============================================
function safe_sql_query($query)
{
    if (function_exists('sql_query')) return sql_query($query);
    global $connect_db;
    if ($connect_db) return mysqli_query($connect_db, $query);
    return false;
}

function safe_sql_fetch_array($result)
{
    if (function_exists('sql_fetch_array')) return sql_fetch_array($result);
    if ($result) return mysqli_fetch_assoc($result);
    return false;
}

// ============================================
// 칼럼 데이터 가져오기
// ============================================
$col_result = safe_sql_query("SELECT * FROM " . BOARD_PREFIX . COLUMN_BOARD . " WHERE wr_is_comment = 0 ORDER BY wr_datetime DESC LIMIT 4");
$columns = [];
if ($col_result) {
    while ($col = safe_sql_fetch_array($col_result)) {
        $thumb = '/images/column.png';
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', stripslashes($col['wr_content']), $m)) {
            $thumb = $m[1];
        }
        $columns[] = [
            'wr_id'   => $col['wr_id'],
            'subject' => $col['wr_subject'],
            'date'    => $col['wr_datetime'] ? date('Y.m.d', strtotime($col['wr_datetime'])) : '',
            'thumb'   => $thumb,
        ];
    }
}

// ============================================
// 의료진 데이터 가져오기
// ============================================
$doctors    = [];
$doc_result = sql_query("SELECT * FROM dm_doctors WHERE is_visible = 1 ORDER BY display_order ASC, id ASC");
if ($doc_result) {
    while ($d = sql_fetch_array($doc_result)) {
        $d['degree'] = $d['degree'] ? json_decode($d['degree'], true) : [];
        $doctors[] = $d;
    }
}
$doc_no_img = '/images/docThumb01.png';

// ============================================
// 게시글 데이터 가져오기
// ============================================
$review_result = safe_sql_query("SELECT * FROM " . BOARD_PREFIX . REVIEW_BOARD . " ORDER BY wr_datetime DESC LIMIT 3");
$case_result   = safe_sql_query("SELECT * FROM " . BOARD_PREFIX . CASE_BOARD   . " ORDER BY wr_datetime DESC LIMIT 6");

function get_content_thumbnail($content)
{
    if (!$content) return null;
    $content = stripslashes($content);
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $match)) {
        return $match[1];
    }
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $content, $match)) {
        return 'https://i.ytimg.com/vi/' . $match[1] . '/hqdefault.png';
    }
    return null;
}

// ============================================
// 팝업 HTML
// ============================================
$popup_html = '';
if (file_exists('./popup.php')) {
    ob_start();
    include_once('./popup.php');
    $popup_html = ob_get_clean();
}

// ============================================
// 리뷰 데이터 가져오기
// ============================================
$cat_map = [
    '임플란트'        => 'implant',
    '심미치료'        => 'cosmetic',
    '자연치아 살리기' => 'natural',
    '기타'            => 'etc',
];

$review_tabs = [];
foreach ($cat_map as $cat_name => $tab_id) {
    $esc = sql_real_escape_string($cat_name);
    $r   = safe_sql_query("SELECT * FROM dm_review WHERE is_visible = 1 AND category = '{$esc}' ORDER BY review_date DESC, id DESC LIMIT 4");
    $review_tabs[$tab_id] = [];
    if ($r) while ($row = safe_sql_fetch_array($r)) $review_tabs[$tab_id][] = $row;
}

$r_all      = safe_sql_query("SELECT * FROM dm_review WHERE is_visible = 1 ORDER BY review_date DESC, id DESC LIMIT 4");
$review_all = [];
if ($r_all) while ($row = safe_sql_fetch_array($r_all)) $review_all[] = $row;

$is_logged_in = !empty($member['mb_id']);

function render_review_card($review, $is_logged_in)
{
    $id    = (int)$review['id'];
    $title = htmlspecialchars($review['title'] ?? '');
    $date  = $review['review_date'] ? date('Y.m.d', strtotime($review['review_date'])) : '';
    $thumb = '/images/imsang.png';
    if (!empty($review['content'])) {
        $content_clean = stripslashes($review['content']);
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content_clean, $m)) {
            $thumb = htmlspecialchars($m[1]);
        }
    }
    $overlay_style = $is_logged_in ? ' style="display:none;"' : '';
    ob_start();
?>
    <a href="/sub/case_review.php?id=<?php echo $id; ?>" class="box d-block text-decoration-none"
        data-aos="fade-up" data-aos-duration="1000" data-aos-delay="500">
        <div class="imgBox position-relative u-round-30">
            <img class="w-100" src="<?php echo $thumb; ?>" alt="<?php echo $title; ?>" />
            <div class="overlay u-round-20 w-100 h-100 position-absolute d-flex justify-content-center align-items-center flex-column"
                style="top:0;left:0;background-color:rgba(0,0,0,0.8);"
                <?php echo $overlay_style; ?>>
                <h6 class="u-txt-white t-fs-14 t-fs-16-sm u-lh-18 fw-bold">
                    전, 후 케이스는 로그인 후 확인 가능합니다.
                </h6>
                <p class="c-fs-8 u-lh-15 fw-300 u-txt-gray-500">
                    ※ 서울다온치과는 의료법을 준수하며 해당 게시물은<br />
                    의료법 제56조에 의거하여 로그인 후 열람이 가능합니다.
                </p>
            </div>
        </div>
        <div class="titleBox u-p-30">
            <h5 class="u-txt-black fw-500 t-fs-20 u-lh-16 u-ls-10 u-mb-8">
                <?php echo $title; ?>
            </h5>
            <p class="u-txt-gray-700 c-fs-18 fw-400 u-lh-16 u-ls-10">
                <?php echo $date; ?>
            </p>
        </div>
    </a>
<?php
    return ob_get_clean();
}

function render_empty_tab()
{
    return '<div class="d-flex justify-content-center align-items-center" style="min-height:300px;grid-column:1/-1;">
        <p class="u-txt-gray-500 c-fs-20 fw-400">작성된 리뷰가 없습니다.</p>
    </div>';
}

include_once(G5_PATH . '/head_sub.php');
// include_once(G5_PATH . '/head.php');

// ============================================
// SEO / 스키마
// ============================================
$g5['title'] = '';

$schema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Dentist',
    'name'        => $site_config['cf_site_name']    ?? '',
    'description' => $site_config['cf_description']  ?? '',
    'url'         => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    'telephone'   => $site_config['cf_tel']           ?? '',
    'address'     => [
        '@type'          => 'PostalAddress',
        'streetAddress'  => $site_config['cf_address'] ?? '',
        'addressCountry' => 'KR',
    ],
    'sameAs' => array_filter([
        $site_config['cf_naver_booking'] ?? '',
        $site_config['cf_kakao']         ?? '',
        $site_config['cf_blog']          ?? '',
        $site_config['cf_instagram']     ?? '',
    ]),
];
echo '<script type="application/ld+json">'
    . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
    . '</script>';
?>

<!-- 갤러리 CSS/JS -->
<?php include './page/gallery.php'; ?>
<link rel="stylesheet" href="/page/gallery.css">
<script src="/page/gallery.js"></script>

<!-- 팝업 -->
<?= $popup_html ?>

<!-- ============================================ -->
<!-- 메인 콘텐츠 -->
<!-- ============================================ -->
<main class="u-pretendard main">
    <!-- 임시 페이지 -->
    <div class="imsi position-fixed w-100 vh-100" style="z-index: 99999;">
        <div class="w-100 h-100 d-none d-lg-flex justify-content-center align-items-center"
            style="background-image: url('/images/imsibg.png'); background-position: center; background-size: cover; padding:  0; overflow-y: scroll;">
            <img style="width: 100%;" src="/images/imsi.png" alt="">
        </div>
        <div class="w-100 d-flex d-lg-none position-relative justify-content-center align-items-start"
            style="height:100dvh; overflow-y:auto; -webkit-overflow-scrolling:touch;padding:0;">
            <img style="width:100%; height:auto; display:block;" src="/images/imsimo.png" alt="">
            <a href="tel:<?= preg_replace('/[^0-9]/', '', $site_config['cf_tel'] ?? '') ?>"
                class="w-100 d-block"
                style="position:absolute; bottom:0; left:0; height:10vh;"></a>
        </div>
    </div>
</main>


<style>
    .imsi::-webkit-scrollbar {
        display: none!important;
    }
</style>

<?php include_once(G5_PATH . '/tail_sub.php'); ?>