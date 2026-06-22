<?php
chdir(dirname(__FILE__) . '/..');
include_once('./_common.php');

global $connect_db;
$db = $connect_db;

$table        = 'g5_column';
$column_path  = '/sub';
$column_label = '원장님 칼럼';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /');
    exit;
}

$post = mysqli_fetch_assoc(mysqli_query($db, "SELECT * FROM `{$table}` WHERE id={$id}"));
if (!$post) {
    header('Location: /');
    exit;
}

// 카테고리명
$cat_name = '미분류';
if (!empty($post['cat_id'])) {
    $cat_row = mysqli_fetch_assoc(mysqli_query($db, "SELECT name FROM g5_column_cat WHERE id=" . (int)$post['cat_id']));
    if ($cat_row) $cat_name = $cat_row['name'];
}

// 조회수
if (empty($_SESSION['viewed_col_' . $id])) {
    mysqli_query($db, "UPDATE `{$table}` SET hit=hit+1 WHERE id={$id}");
    $_SESSION['viewed_col_' . $id] = true;
    $post['hit'] = (int)$post['hit'] + 1;
}

// 이전/다음
$prev = mysqli_fetch_assoc(mysqli_query($db, "SELECT id,title FROM `{$table}` WHERE id > {$id} ORDER BY id ASC  LIMIT 1"));
$next = mysqli_fetch_assoc(mysqli_query($db, "SELECT id,title FROM `{$table}` WHERE id < {$id} ORDER BY id DESC LIMIT 1"));

$date = date('Y.m.d', strtotime($post['created_at']));
$tags = array_filter(array_map('trim', explode(',', $post['tags'] ?? '')));

// FAQ
$faqs = [];
if (!empty($post['faq'])) {
    $d = json_decode($post['faq'], true);
    if (is_array($d)) $faqs = $d;
}

// ── 블록 에디터 JSON → HTML 변환 ──────────────────────────
function render_block_content($json_str)
{
    $data = json_decode($json_str, true);
    if (!$data || !isset($data['rows'])) return $json_str;
    $html = '';
    foreach ($data['rows'] as $row) {
        $sec_id = !empty($row['sectionId']) ? ' id="' . htmlspecialchars($row['sectionId']) . '"' : '';
        $html .= '<div class="pb-row"' . $sec_id . '>';
        foreach ($row['columns'] as $col) {
            $span = (int)($col['span'] ?? 12);
            $html .= '<div class="pb-col pb-col-' . $span . '">';
            foreach ($col['blocks'] as $block) {
                $d = $block['data'] ?? [];
                $s = $d['style'] ?? [];
                $si = '';
                if (!empty($s['fontSize']))     $si .= 'font-size:'      . (int)$s['fontSize']  . 'px;';
                if (!empty($s['fontWeight']))    $si .= 'font-weight:'    . $s['fontWeight']      . ';';
                if (!empty($s['textAlign']))     $si .= 'text-align:'     . $s['textAlign']       . ';';
                if (!empty($s['color']))         $si .= 'color:'          . $s['color']           . ';';
                if (!empty($s['lineHeight']))    $si .= 'line-height:'    . $s['lineHeight']      . ';';
                if (!empty($s['letterSpacing'])) $si .= 'letter-spacing:' . $s['letterSpacing']  . 'px;';
                if (!empty($d['bgColor']))       $si .= 'background:'     . $d['bgColor']         . ';';
                $style = $si ? ' style="' . $si . '"' : '';
                switch ($block['type']) {
                    case 'paragraph': $html .= '<p'.$style.'>' . ($d['text'] ?? '') . '</p>'; break;
                    case 'h2': $html .= '<h2'.$style.'>' . ($d['text'] ?? '') . '</h2>'; break;
                    case 'h3': $html .= '<h3'.$style.'>' . ($d['text'] ?? '') . '</h3>'; break;
                    case 'h4': $html .= '<h4'.$style.'>' . ($d['text'] ?? '') . '</h4>'; break;
                    case 'quote': $html .= '<blockquote class="pb-quote"'.$style.'>' . ($d['text'] ?? '') . '</blockquote>'; break;
                    case 'warning':
                        $html .= '<div class="pb-warning"'.$style.'><strong>' . ($d['title'] ?? '') . '</strong><p>' . ($d['text'] ?? '') . '</p></div>';
                        break;
                    case 'image':
                        if (!empty($d['url'])) {
                            $br  = (int)($d['borderRadius'] ?? 6);
                            $alt = htmlspecialchars($d['alt'] ?? '');
                            $html .= '<img src="' . htmlspecialchars($d['url']) . '" alt="' . $alt . '" style="max-width:100%;border-radius:' . $br . 'px;display:block;">';
                        }
                        break;
                    case 'list':
                        $html .= '<ul'.$style.'>';
                        foreach ($d['items'] ?? [] as $item) $html .= '<li>' . $item . '</li>';
                        $html .= '</ul>';
                        break;
                    case 'delimiter': $html .= '<hr class="pb-hr">'; break;
                    case 'table':
                        if (!empty($d['rows'])) {
                            $html .= '<table class="pb-table"'.$style.'>';
                            foreach ($d['rows'] as $ri => $trow) {
                                $html .= '<tr>';
                                foreach ($trow as $cell) {
                                    $html .= ($ri === 0) ? '<th>' . $cell . '</th>' : '<td>' . $cell . '</td>';
                                }
                                $html .= '</tr>';
                            }
                            $html .= '</table>';
                        }
                        break;
                    case 'button':
                        $href = htmlspecialchars($d['href'] ?? '#');
                        $html .= '<div class="pb-btn-wrap"><a class="pb-btn"'.$style.' href="'.$href.'">' . ($d['text'] ?? '버튼') . '</a></div>';
                        break;
                    case 'faq':
                        if (!empty($d['items'])) {
                            $html .= '<div class="pb-faq">';
                            foreach ($d['items'] as $fi) {
                                $html .= '<details class="pb-faq-item">';
                                $html .= '<summary class="pb-faq-q">' . ($fi['q'] ?? '') . '</summary>';
                                $html .= '<div class="pb-faq-a">' . ($fi['a'] ?? '') . '</div>';
                                $html .= '</details>';
                            }
                            $html .= '</div>';
                        }
                        break;
                }
            }
            $html .= '</div>';
        }
        $html .= '</div>';
    }
    return $html;
}

$editor_type = $post['editor_type'] ?? 'html';
$raw = $post['content'] ?? '';
// 저장 시 mysqli_real_escape_string으로 생긴 백슬래시 제거
$raw = stripslashes($raw);

// editor_type 컬럼이 없거나 기본값 'html'이어도
// JSON에 'rows' 키가 있으면 블록 에디터 콘텐츠로 자동 감지
if ($editor_type !== 'builder') {
    $_test = json_decode($raw, true);
    if (is_array($_test) && isset($_test['rows'])) {
        $editor_type = 'builder';
    }
}

$content = ($editor_type === 'builder')
    ? render_block_content($raw)
    : $raw;

// ── 상대경로 이미지 제거 (서버에서 접근 불가) ─────────────
$content = preg_replace_callback('/<img[^>]+>/i', function($m) {
    if (preg_match('/src=["\']([^"\']*)["\']/', $m[0], $s)) {
        $src = $s[1];
        // http/https 또는 /로 시작하지 않으면 제거
        if (!preg_match('/^https?:\/\//i', $src) && strpos($src, '/') !== 0) {
            return '';
        }
    }
    return $m[0];
}, $content);

// SEO
$dm_conf = mysqli_fetch_assoc(mysqli_query($db, "SELECT cf_site_name, cf_doctors FROM dm_config WHERE cf_id=1"));
$hospital_name = $dm_conf['cf_site_name'] ?? $_SERVER['HTTP_HOST'];
$site_url  = 'https://' . $_SERVER['HTTP_HOST'];
$page_url  = $site_url . $_SERVER['REQUEST_URI'];

$g5['title']      = $post['title'];
$page_description = !empty($post['meta_desc'])
    ? $post['meta_desc']
    : mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($content))), 0, 160, 'UTF-8');

// canonical — id 파라미터 포함 (head_sub의 path-only canonical을 override)
$protocol       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$page_canonical = $protocol . '://' . $_SERVER['HTTP_HOST'] . $column_path . '/column.php?id=' . $id;

include_once(G5_PATH . '/head_sub.php');
include_once(G5_PATH . '/head.php');
?>

<main class="u-pretendard column-detail u-pb-80">
    <div class="u-mb-80 u-bg-main u-pt-150 u-pb-40">
        <div class="headerWrap">
            <h3 class="u-txt-point c-fs-16 fw-600 u-lh-15 u-ls-10 u-mb-20 u-bd-1 u-b-solid u-bc-point u-px-8 u-py-4 w-max"><?= htmlspecialchars($cat_name) ?></h3>
            <h1 class="u-txt-white t-fs-28 fw-600 u-maruburi u-lh-16 u-ls-10 u-mb-20"><?= htmlspecialchars($post['title']) ?></h1>
            <div class="d-flex justify-content-start align-items-center u-gap-8">
                <p class="u-txt-gray-500 c-fs-12 fw-400 u-ls-10"><?= $date ?></p>
                <p class="u-txt-gray-500 c-fs-12 fw-400 u-ls-10">ㆍ</p>
                <p class="u-txt-gray-500 c-fs-12 fw-400 u-ls-10"><span class="">조회수</span>&nbsp; <?= number_format((int)$post['hit']) ?></p>
            </div>
        </div>
    </div>
    <!-- 모바일 목차 (content 직전) -->
    <div class="toc-mobile" id="tocMobile">
        <button class="toc-mobile-toggle" onclick="document.getElementById('tocMobileBody').classList.toggle('open')">
            <span class="material-symbols-outlined" style="font-size:16px;">menu_book</span>
            목차 보기
            <span class="material-symbols-outlined toc-chevron" style="font-size:16px;">expand_more</span>
        </button>
        <div class="toc-mobile-body" id="tocMobileBody">
            <nav id="tocNavMobile"></nav>
        </div>
    </div>

    <div class="contentWrap">
        <div class="content-layout">
            <div id="content">
                <?= $content ?>
            </div>
            <aside class="toc-sidebar" id="tocSidebar">
                <div class="toc-inner">
                    <p class="toc-title">
                        <span class="material-symbols-outlined" style="font-size:15px;">menu_book</span>목차
                    </p>
                    <nav id="tocNav"></nav>
                </div>
            </aside>
        </div>

        </div><!-- /content-layout -->

        <div class="u-bt-1 u-bc-gray-300 u-py-40 content-footer">
            <h2 class="u-mt-48 u-txt-dark t-fs-16 fw-600 u-mb-8 u-notoserif text-center"><?= htmlspecialchars($hospital_name) ?></h2>
            <p class="u-txt-gray-700 u-lh-20 c-fs-14 text-center">좋은 치료를 위해 온 마음을 담는 <?= htmlspecialchars($hospital_name) ?>입니다. 감사합니다.</p>
            <div class="u-px-20 u-mt-60">
                <?php
                $has_treat = !empty($post['treat_start']) || !empty($post['treat_end']) || !empty($post['treat_side']);
                ?>
                <?php if ($has_treat): ?>
                    <div class="u-bd-1 u-round-8 u-px-24 u-py-16 u-mb-100 u-bc-gray-300 u-b-solid">
                        <h6 class="u-txt-point u-mb-16 fw-700 t-fs-14">치료 정보</h6>
                        <ul>
                            <?php if (!empty($post['treat_start'])): ?>
                                <li><p class="u-txt-gray-500 u-mb-16 fw-400 t-fs-14"><strong class="u-txt-dark fw-600">치료 시작일:</strong> <?= htmlspecialchars($post['treat_start']) ?></p></li>
                            <?php endif; ?>
                            <?php if (!empty($post['treat_end'])): ?>
                                <li><p class="u-txt-gray-500 u-mb-16 fw-400 t-fs-14"><strong class="u-txt-dark fw-600">치료 종료일:</strong> <?= htmlspecialchars($post['treat_end']) ?></p></li>
                            <?php endif; ?>
                            <?php if (!empty($post['treat_side'])): ?>
                                <li><p class="u-txt-gray-500 u-mb-16 fw-400 t-fs-14"><strong class="u-txt-dark fw-600">부작용 및 합병증:</strong> <?= htmlspecialchars($post['treat_side']) ?></p></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="u-round-8 u-px-24 u-py-16 u-bg-main">
                    <h6 class="u-txt-point u-mb-16 fw-700 t-fs-14">의료법상 고지 의무 사항</h6>
                    <p class="c-fs-16 u-mb-20 u-txt-gray-700 fw-500 u-lh-15 c-fs-20 fw-400"><strong><?= htmlspecialchars($hospital_name) ?> 블로그의 모든 게시물은 환자분께 의학적으로 정확하고 상세한 정보를 제공하기 위해 대표원장이 직접 작성합니다.</strong></p>
                    <ul>
                        <li class="u-txt-gray-500 c-fs-12 fw-400 u-gap-8 d-flex align-items-start"><span class="d-block">ㆍ</span>본 포스팅은 의료 정보 제공 및 광고를 목적으로 환자분의 동의를 받아 의료법 제56조 및 의료법 시행령 제23조를 준수하여 작성하였습니다.</li>
                        <li class="u-txt-gray-500 c-fs-12 fw-400 u-gap-8 d-flex align-items-start"><span class="d-block">ㆍ</span>사용된 모든 사진은 본원에서 치료받은 환자로, 동일 부위를 같은 환경에서 촬영하였으며, 일체의 후보정작업은 거치지 않았습니다.</li>
                        <li class="u-txt-gray-500 c-fs-12 fw-400 u-gap-8 d-flex align-items-start"><span class="d-block">ㆍ</span>치료의 결과는 환자들마다 상이할 수 있으며, 이 치료 결과는 본 케이스에만 해당되는 것입니다.</li>
                        <li class="u-txt-gray-500 c-fs-12 fw-400 u-gap-8 d-flex align-items-start"><span class="d-block">ㆍ</span>모든 치과 시술은 개인의 특성에 따라 크고 작은 부작용이 발생될 수 있습니다.</li>
                        <li class="u-txt-gray-500 c-fs-12 fw-400 u-gap-8 d-flex align-items-start"><span class="d-block">ㆍ</span>정확한 사항은 경험이 풍부한 의료진과 충분히 상의하신 후에 시술을 결정하시기 바랍니다.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<style>

    /* 관련 링크 박스 */
.related-link-box { display:flex; align-items:stretch; border:1px solid #e8eaf0; border-radius:12px; overflow:hidden; text-decoration:none; margin:24px 0; background:#fff; transition:box-shadow .2s, border-color .2s; }
.related-link-box:hover { border-color:var(--sub-color,#4a7ab5); box-shadow:0 4px 16px rgba(0,0,0,.08); }
.rlb-img { width:120px; min-width:120px; flex-shrink:0; background:#f5f6fa; overflow:hidden; display:flex; align-items:center; justify-content:center; }
.rlb-img img { width:100%; height:100%; object-fit:cover; display:block; }
.rlb-img-ph { font-size:28px; color:#c4b5fd; }
.rlb-content { flex:1; display:flex; flex-direction:column; justify-content:center; min-width:0; }
.related-link-box .rlb-body { padding:14px 16px 10px; }
.related-link-box .rlb-title { font-size:14px; font-weight:700; color:#1a1a2e; margin-bottom:5px; line-height:1.4; }
.related-link-box .rlb-desc { font-size:12px; color:#6b7280; line-height:1.6; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.related-link-box .rlb-url { padding:6px 16px; background:#f5f6fa; font-size:11px; color:var(--main-color,#25456b); border-top:1px solid #e8eaf0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
@media(max-width:600px){ .rlb-img{width:80px;min-width:80px;} }
    .headerWrap {
        max-width: 1040px;
        margin: 0 auto;
        width: 100%;
        padding: 0 24px;
    }
    .contentWrap {
        max-width: 1040px;
        margin: 0 auto;
        width: 100%;
        padding: 0 24px;
    }
    .content-footer {
         max-width: 1040px;
        margin: 0 auto;
        width: 100%;
        padding: 0 24px;
        margin-top: 48px!important;
    }

    /* ── 목차 + 콘텐츠 레이아웃 ── */
    .content-layout { display: flex; gap: 48px; align-items: flex-start; position: relative;}
    #content { flex: 1; min-width: 0; max-width: 630px; font-size: 15px; line-height: 1.95; color: #333; }

    /* ── 목차 사이드바 (PC) ── */
    .toc-sidebar { width: 200px; flex-shrink: 0; position: sticky; top: 20px; right: 0;}
    .toc-inner { position: sticky; top: 120px; background: #f8f7f5; border-radius: 10px; border: 1px solid #e8e4de; padding: 16px; }
    .toc-title { font-size: 12px; font-weight: 700; color: var(--main-color, #25456b); display: flex; align-items: center; gap: 5px; margin: 0 0 10px; padding-bottom: 8px; border-bottom: 1px solid #e8e4de; }
    #tocNav ul, #tocNavMobile ul { list-style: none; padding: 0; margin: 0; }
    #tocNav li, #tocNavMobile li { margin-bottom: 2px; }
    #tocNav .toc-h2 > a, #tocNavMobile .toc-h2 > a { font-size: 12px; font-weight: 700; color: #374151; text-decoration: none; display: block; padding: 4px 6px; border-radius: 5px; line-height: 1.5; transition: all .15s; }
    #tocNav .toc-h3 > a, #tocNavMobile .toc-h3 > a { font-size: 11px; color: #6b7280; text-decoration: none; display: block; padding: 3px 6px 3px 14px; border-radius: 5px; line-height: 1.5; transition: all .15s; }
    #tocNav a:hover, #tocNavMobile a:hover { background: #ede9fe; color: var(--main-color, #25456b); }
    #tocNav a.active, #tocNavMobile a.active { background: var(--main-color, #25456b); color: #fff !important; font-weight: 700; }

    /* ── 모바일 목차 ── */
    .toc-mobile { display: none; max-width: 630px; margin: 0 auto 20px; }
    .toc-mobile-toggle { width: 100%; display: flex; align-items: center; gap: 6px; padding: 12px 16px; background: #f8f7f5; border: 1px solid #e8e4de; border-radius: 8px; font-size: 13px; font-weight: 700; color: var(--main-color, #25456b); cursor: pointer; font-family: inherit; }
    .toc-chevron { margin-left: auto; transition: transform .2s; }
    .toc-chevron.open { transform: rotate(180deg); }
    .toc-mobile-body { display: none; padding: 12px 16px; border: 1px solid #e8e4de; border-top: none; border-radius: 0 0 8px 8px; background: #fff; }
    .toc-mobile-body.open { display: block; }

    /* ── 본문 공통 ── */
    #content p { font-size: 15px; line-height: 1.95; color: #333; margin-bottom: 18px; }
    #content strong { font-weight: 700; }
    #content a { color: var(--sub-color, #2563eb); }
    #content img { max-width: 100%; border-radius: 6px; margin: 24px 0; display: block; width: 100% }

    /* H2 — main-color + sub-color 보더 */
    #content h2 {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 20px;
        font-weight: 700;
        color: var(--main-color, #25456b);
        margin: 44px 0 16px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--sub-color, #4a7ab5);
        scroll-margin-top: 120px;
    }
    #content h2::before {
        content: '';
        display: block;
        width: 4px;
        min-width: 4px;
        height: 22px;
        background: var(--main-color, #25456b);
        border-radius: 2px;
        flex-shrink: 0;
    }

    /* H3 */
    #content h3 { font-size: 16px; font-weight: 700; color: #1a1a1a; margin: 28px 0 10px; scroll-margin-top: 120px; }

    /* H4 */
    #content h4 { font-size: 14px; font-weight: 700; color: #333; margin: 20px 0 8px; }

    /* H6 — 인용 강조 박스 */
    #content h6 {
        border: 1px solid #e8d9bc;
        border-left: 4px solid var(--sub-color, #e8a020);
        border-radius: 0 8px 8px 0;
        padding: 14px 20px;
        margin: 20px 0;
        font-size: 14.5px;
        font-weight: 500;
        color: #854d0e;
        background: linear-gradient(135deg, #fdf8f0, #faf4e8);
        font-style: italic;
        line-height: 1.7;
    }

    /* figcaption */
    #content figcaption { text-align: center; color: #9ca3af; font-size: 13px; margin-top: 8px; line-height: 1.5; }

    /* blockquote */
    #content blockquote {
        border: 1px solid #e8e4de;
        border-radius: 8px;
        padding: 22px 26px 22px 40px;
        margin: 28px 0;
        background: #f8f7f5;
        font-size: 14.5px;
        color: #333;
        line-height: 1.8;
        position: relative;
    }
    #content blockquote::before {
        content: '"';
        font-size: 52px;
        color: var(--main-color, #25456b);
        opacity: 0.2;
        position: absolute;
        top: 6px;
        left: 14px;
        line-height: 1;
        font-family: Georgia, serif;
    }
    #content blockquote p { margin: 0; }

    /* ul / ol */
    #content ul, #content ol { padding-left: 22px; margin: 0 0 18px; }
    #content li { font-size: 15px; line-height: 1.85; color: #333; margin-bottom: 6px; }

    /* hr */
    #content hr { border: none; border-top: 1px solid #e8e4de; margin: 40px 0; }

    /* ── 테이블 ── */
    #content table { width: 100%; border-collapse: collapse; margin: 24px 0; font-size: 14px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.07); }
    #content table caption { font-size: 13px; color: #6b7280; margin-bottom: 8px; text-align: center; }
    #content table th { background: var(--main-color, #25456b); color: var(--point, #FABE00); padding: 12px 16px; font-weight: 700; text-align: left; font-size: 13px; }
    #content table td { padding: 12px 16px; border: 1px solid #e5e7eb; vertical-align: top; line-height: 1.7; color: #374151; }
    #content table tr:nth-child(even) td { background: #f9fafb; }
    #content table tr:hover td { background: #f0f4f8; }
    #content table th + th, #content table td + td { border-left: 1px solid #e5e7eb; }

    /* ── FAQ details/summary ── */
    .faq-item { margin-bottom: 10px; }
    .faq-item details { border: 1px solid #e8e4de; border-radius: 10px; overflow: hidden; }
    .faq-item details summary {
        padding: 16px 20px;
        background: #f8f7f5;
        cursor: pointer;
        font-weight: 600;
        font-size: 15px;
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        color: #1a1a1a;
        transition: background .15s;
    }
    .faq-item details summary::-webkit-details-marker { display: none; }
    .faq-item details summary::after { content: '+'; font-size: 22px; font-weight: 300; color: var(--main-color, #25456b); flex-shrink: 0; }
    .faq-item details[open] summary { background: var(--main-color, #25456b); color: #fff; }
    .faq-item details[open] summary::after { content: '−'; color: #fff; }
    .faq-item .faq-answer { padding: 16px 20px; font-size: 14.5px; line-height: 1.85; color: #374151; border-top: 1px solid #e8e4de; background: #fff; }

    /* ── 블록 에디터 전용 ── */
    .pb-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 24px; }
    .pb-col { flex: 1; min-width: 0; }
    .pb-col-1{flex:1} .pb-col-2{flex:2} .pb-col-3{flex:3} .pb-col-4{flex:4}
    .pb-col-5{flex:5} .pb-col-6{flex:6} .pb-col-7{flex:7} .pb-col-8{flex:8}
    .pb-col-9{flex:9} .pb-col-10{flex:10} .pb-col-11{flex:11} .pb-col-12{flex:12}
    .pb-quote { border:1px solid #e8e4de; border-radius:8px; padding:22px 26px 22px 40px; margin:28px 0; background:#f8f7f5; font-size:14.5px; color:#333; line-height:1.8; position:relative; }
    .pb-quote::before { content:'"'; font-size:52px; color:var(--main-color,#25456b); opacity:.2; position:absolute; top:6px; left:14px; line-height:1; font-family:Georgia,serif; }
    .pb-warning { background:linear-gradient(135deg,#fdf8f0,#faf4e8); border:1px solid #e8d9bc; border-radius:8px; padding:20px 24px; margin:28px 0; }
    .pb-warning strong { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--main-color,#25456b); display:block; margin-bottom:8px; }
    .pb-warning p { font-size:14px; color:#333; margin:0; line-height:1.8; }
    .pb-row h2 { display:flex; align-items:center; gap:12px; font-size:20px; font-weight:700; color:var(--main-color,#25456b); margin:44px 0 16px; padding-bottom:12px; border-bottom:2px solid var(--sub-color,#4a7ab5); }
    .pb-row h2::before { content:''; display:block; width:4px; min-width:4px; height:22px; background:var(--main-color,#25456b); border-radius:2px; }
    .pb-row h3 { font-size:16px; font-weight:700; color:#1a1a1a; margin:28px 0 10px; }
    .pb-hr { border:none; border-top:1px solid #e8e4de; margin:40px 0; }
    .pb-table { width:100%; border-collapse:collapse; margin:8px 0; font-size:14px; }
    .pb-table td, .pb-table th { border:1.5px solid #e5e7eb; padding:8px 12px; }
    .pb-table th { background:var(--main-color,#25456b); color:var(--point,#FABE00); font-weight:700; }
    .pb-table tr:nth-child(even) td { background:#f9fafb; }

    /* FAQ details/summary */
    .pb-faq { display:flex; flex-direction:column; gap:0; border:1.5px solid #e8eaf0; border-radius:12px; overflow:hidden; margin:20px 0; }
    .pb-faq-item { border-bottom:1px solid #e8eaf0; }
    .pb-faq-item:last-child { border-bottom:none; }
    .pb-faq-q { list-style:none; padding:15px 18px; font-size:15px; font-weight:600; color:#1a1a2e; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:12px; user-select:none; transition:background .15s; }
    .pb-faq-q:hover { background:#f8f9fb; }
    .pb-faq-q::marker,.pb-faq-q::-webkit-details-marker { display:none; }
    .pb-faq-q::after { content:'\002B'; font-size:16px; color:#99b5d7; flex-shrink:0; }
    .pb-faq-item[open] > .pb-faq-q::after { content:'\2212'; color:var(--main-color,#25456b); }
    .pb-faq-item[open] > .pb-faq-q { color:var(--main-color,#25456b); background:#f0f4ff; }
    .pb-faq-a { padding:12px 18px 16px; font-size:14px; line-height:1.8; color:#555; border-top:1px solid #f0f3f8; }
    /* figure/figcaption */
    .pb-figure { margin:16px 0; }
    .pb-figcaption { font-size:12px; color:#8090b0; text-align:center; margin-top:8px; font-style:italic; }
    .pb-btn-wrap { margin:8px 0; }
    .pb-btn { display:inline-flex; padding:10px 22px; border-radius:8px; background:var(--main-color,#25456b); color:#fff; font-weight:700; text-decoration:none; font-size:14px; }

    /* ── 반응형 ── */
    @media (max-width: 992px) {
        .content-layout { flex-direction: column; }
        .toc-sidebar { display: none; }
        .toc-mobile { display: block; }
        .toc-mobile-body {
            display: block;
        }
        #content { max-width: 100%; }
        .pb-row { flex-direction: column; }
        [class*="pb-col-"] { flex: 0 0 100% !important; }
            .toc-sidebar { width: 200px; flex-shrink: 0; position: static; top: 0; right: 0;}
    }
    @media (max-width: 992px) {
        .headerWrap, .contentWrap { padding: 0 16px; }
    }
</style>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
<script>
document.addEventListener('DOMContentLoaded', function() {
    var content = document.getElementById('content');
    if (!content) return;

    /* ── FAQ: 옛 마이크로데이터(.faq-item 안에 h3)만 details로 변환.
       새 메이커/블록에디터 출력은 이미 <details>이므로 건드리지 않는다. ── */
    content.querySelectorAll('.faq-item').forEach(function(item) {
        // 이미 details이거나 내부에 details/summary가 있으면 변환된 것 → 스킵
        if (item.tagName === 'DETAILS' || item.querySelector('details, summary')) return;
        var h3  = item.querySelector('h3[itemprop="name"], h3');
        var ans = item.querySelector('[itemprop="acceptedAnswer"], [class*="answer"], div');
        if (!h3) return;
        var details = document.createElement('details');
        var summary = document.createElement('summary');
        summary.textContent = h3.textContent.replace(/^Q\.\s*/,'Q. ');
        var ansDiv = document.createElement('div');
        ansDiv.className = 'faq-answer';
        ansDiv.innerHTML = ans ? ans.innerHTML : '';
        details.appendChild(summary);
        details.appendChild(ansDiv);
        item.innerHTML = '';
        item.appendChild(details);
    });

    /* ── 목차 생성 ── */
    var headings = content.querySelectorAll('h2, h3');
    if (headings.length === 0) return;

    function buildToc(navEl) {
        var ul = document.createElement('ul');
        headings.forEach(function(h, i) {
            if (!h.id) h.id = 'toc-h-' + i;
            var li = document.createElement('li');
            li.className = h.tagName === 'H2' ? 'toc-h2' : 'toc-h3';
            var a = document.createElement('a');
            a.href = '#' + h.id;
            // h2는 ::before 막대 때문에 textContent가 공백 포함 — trim
            a.textContent = h.textContent.trim();
            a.addEventListener('click', function(e) {
                e.preventDefault();
                h.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            li.appendChild(a);
            ul.appendChild(li);
        });
        navEl.appendChild(ul);
    }

    var tocNav    = document.getElementById('tocNav');
    var tocNavMob = document.getElementById('tocNavMobile');
    if (tocNav)    buildToc(tocNav);
    if (tocNavMob) buildToc(tocNavMob);

    /* ── 스크롤 감지 → active ── */
    function setActive(id) {
        [tocNav, tocNavMob].forEach(function(nav) {
            if (!nav) return;
            nav.querySelectorAll('a').forEach(function(a) { a.classList.remove('active'); });
            var link = nav.querySelector('a[href="#' + id + '"]');
            if (link) link.classList.add('active');
        });
    }

    var io = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) setActive(entry.target.id);
        });
    }, { rootMargin: '-10% 0px -75% 0px' });

    headings.forEach(function(h) { io.observe(h); });

    /* ── 모바일 목차 토글 ── */
    var toggle = document.querySelector('.toc-mobile-toggle');
    var body   = document.getElementById('tocMobileBody');
    var chev   = document.querySelector('.toc-chevron');
    if (toggle && body) {
        toggle.addEventListener('click', function() {
            body.classList.toggle('open');
            if (chev) chev.classList.toggle('open');
        });
    }

    /* ── 관련 링크 박스: DOM 재구성 + OG 이미지 로드 ── */
    document.querySelectorAll('a.related-link-box').forEach(function(box) {
        try {
            // 이미 재구성됐으면 스킵
            if (box.querySelector('.rlb-img')) return;

            // rlb-img 플레이스홀더
            var imgDiv = document.createElement('div');
            imgDiv.className = 'rlb-img';
            imgDiv.innerHTML = '<span class="rlb-img-ph material-symbols-outlined">link</span>';

            // 기존 내용을 rlb-content로 감싸기
            var contentDiv = document.createElement('div');
            contentDiv.className = 'rlb-content';
            while (box.firstChild) contentDiv.appendChild(box.firstChild);

            box.appendChild(imgDiv);
            box.appendChild(contentDiv);

            // OG 이미지 fetch (같은 도메인만)
            var href = box.getAttribute('href') || '';
            if (!href) return;
            var absUrl = href.startsWith('/') ? location.origin + href : href;
            var isSame = absUrl.startsWith(location.origin);
            if (!isSame) return;

            fetch(absUrl, { credentials: 'same-origin' })
                .then(function(r) {
                    if (!r.ok) return null;
                    return r.text();
                })
                .then(function(html) {
                    if (!html) return;
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');

                    // OG 이미지
                    var ogImgEl = doc.querySelector('meta[property="og:image"]');
                    var imgSrc  = ogImgEl ? (ogImgEl.getAttribute('content') || '') : '';
                    if (!imgSrc) {
                        var firstImg = doc.querySelector('main img[src], article img[src], #content img[src]');
                        if (firstImg) {
                            imgSrc = firstImg.getAttribute('src') || '';
                            if (imgSrc && imgSrc.startsWith('/')) imgSrc = location.origin + imgSrc;
                        }
                    }
                    if (imgSrc) {
                        imgDiv.innerHTML = '<img src="' + imgSrc + '" alt="" loading="lazy">';
                    }

                    // title / desc가 비어있으면 OG에서 보완
                    var titleEl = contentDiv.querySelector('.rlb-title');
                    var descEl  = contentDiv.querySelector('.rlb-desc');
                    if (titleEl && !titleEl.textContent.trim()) {
                        var ogTitle = doc.querySelector('meta[property="og:title"]');
                        if (ogTitle) titleEl.textContent = ogTitle.getAttribute('content') || '';
                        if (!titleEl.textContent.trim()) titleEl.textContent = doc.title || '';
                    }
                    if (descEl && !descEl.textContent.trim()) {
                        var ogDesc = doc.querySelector('meta[property="og:description"]');
                        if (!ogDesc) ogDesc = doc.querySelector('meta[name="description"]');
                        if (ogDesc) descEl.textContent = ogDesc.getAttribute('content') || '';
                    }
                })
                .catch(function() {});
        } catch(e) {}
    });

});
</script>

<?php
// ── JSON-LD 스키마 ─────────────────────────────────────
$json_opts  = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
$thumb_url  = !empty($post['thumbnail'])
    ? (strpos($post['thumbnail'], 'http') === 0 ? $post['thumbnail'] : $site_url . $post['thumbnail'])
    : '';

// BreadcrumbList
// (JSON_UNESCAPED_UNICODE 출력이므로 htmlspecialchars 금지 — 이중 인코딩되어 &amp; 등으로 깨짐)
$breadcrumb = [
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    '@id'             => $page_canonical . '#breadcrumb',
    'itemListElement' => [
        ['@type'=>'ListItem','position'=>1,'name'=>$hospital_name,'item'=>$site_url],
        ['@type'=>'ListItem','position'=>2,'name'=>$column_label,'item'=>$site_url.$column_path.'/column_list.php'],
        ['@type'=>'ListItem','position'=>3,'name'=>$post['title'],'item'=>$page_canonical],
    ],
];

// ── author 결정: 대표원장(cf_doctors 첫 번째) Person 참조 ──
// 본문에 "모든 게시물은 대표원장이 직접 작성"이라 명시되어 있어 author=대표원장이 사실과 일치(E-E-A-T).
// cf_doctors가 없으면 기존처럼 병원(#organization)으로 폴백.
$author_ref = ['@id' => $site_url . '/#organization'];
$__docs = [];
if (!empty($dm_conf['cf_doctors'])) {
    $__d = json_decode($dm_conf['cf_doctors'], true);
    if (is_array($__d)) $__docs = $__d;
}
// 이름 있는 첫 원장을 대표원장으로
foreach ($__docs as $__one) {
    if (!empty($__one['name']) && trim($__one['name']) !== '') {
        $author_ref = ['@id' => $site_url . '/#dentist-1'];  // site_config가 생성하는 첫 Person @id와 동일
        break;
    }
}

// Article — author는 대표원장 Person(@id) 참조, publisher는 병원(@id) 참조 → 엔티티 그래프 연결
$article = [
    '@context'      => 'https://schema.org',
    '@type'         => 'Article',
    '@id'           => $page_canonical . '#article',
    'headline'      => $post['title'],
    'description'   => $page_description,
    'url'           => $page_canonical,
    'mainEntityOfPage' => $page_canonical,
    'datePublished' => !empty($post['created_at']) ? date('c', strtotime($post['created_at'])) : '',
    'dateModified'  => !empty($post['updated_at']) ? date('c', strtotime($post['updated_at'])) : (!empty($post['created_at']) ? date('c', strtotime($post['created_at'])) : ''),
    'author'        => $author_ref,
    'publisher'     => ['@id' => $site_url . '/#organization'],
    'inLanguage'    => 'ko-KR',
];
if ($thumb_url) $article['image'] = $thumb_url;

// ── FAQPage 스키마 생성 ───────────────────────────────────
// 본문($content)의 <details>(클래스 faq-item / pb-faq-item)에서 질문·답변을 추출해
// FAQPage JSON-LD를 한 곳(여기)에서만 생성한다.
// 본문에는 마이크로데이터를 넣지 않으므로 중복되지 않는다.
function extract_faqs_from_html($html) {
    $faqs = [];
    if (stripos($html, '<details') === false) return $faqs;

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    // UTF-8 보존
    $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    foreach ($dom->getElementsByTagName('details') as $det) {
        $cls = $det->getAttribute('class');
        // FAQ용 details만 (faq-item 또는 pb-faq-item)
        if (stripos($cls, 'faq-item') === false) continue;

        $q = ''; $a = '';
        foreach ($det->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            $tag = strtolower($child->nodeName);
            if ($tag === 'summary') {
                $q = trim($child->textContent);
            } else {
                // summary 외 모든 요소를 답변 텍스트로 합침
                $a .= ' ' . $child->textContent;
            }
        }
        $q = preg_replace('/^Q[.\s]*/u', '', trim($q));
        $a = preg_replace('/^A[.\s]*/u', '', trim(preg_replace('/\s+/', ' ', $a)));
        if ($q !== '' && $a !== '') {
            $faqs[] = ['q' => $q, 'a' => $a];
        }
    }
    return $faqs;
}

$faq_list = extract_faqs_from_html($content);

$faq_schema = null;
if (!empty($faq_list)) {
    $faq_schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'FAQPage',
        '@id'      => $page_canonical . '#faq',
        'mainEntity' => array_map(function($f) {
            return [
                '@type' => 'Question',
                'name'  => $f['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $f['a'],
                ],
            ];
        }, $faq_list),
    ];
}
?>
<script type="application/ld+json"><?= json_encode($breadcrumb, $json_opts) ?></script>
<script type="application/ld+json"><?= json_encode($article,   $json_opts) ?></script>
<?php if ($faq_schema): ?>
<script type="application/ld+json"><?= json_encode($faq_schema, $json_opts) ?></script>
<?php endif; ?>

<?php include_once(G5_PATH . '/tail.php'); ?>