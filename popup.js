// ══════════════════════════════════════════════
//  설정
// ══════════════════════════════════════════════
const NOTION_API_KEY = "YOUR_NOTION_API_KEY";
const DATABASE_ID    = "32f20d52272f80a1abaac09dd0647728";
const NOTION_VERSION = "2022-06-28";
const USE_DUMMY      = true;

// ══════════════════════════════════════════════
//  더미 데이터 (노션 DB 동일 구조 + 신규 필드)
// ══════════════════════════════════════════════
const DUMMY_DATA = [
  { id:"1",  name:"다인아트치과 리뉴얼",         status:"부류",       start:"2026-02-27", deadline:"2026-03-04", done:"2026-03-12", open:"",           design:"진의영", coding:"김동훈", photo:"",         photoCo:"",      photoDate:"",           url:"",                                    sslStart:"",           hostingStart:"",           hostingInfo:"cafe24",                  ftp:"ftp.dain.com / id: dain / pw: dain123",        domain:"dain.co.kr / 2027-02-27" },
  { id:"2",  name:"진심을다하는치과",            status:"종료",       start:"2026-02-19", deadline:"2026-03-06", done:"2026-03-11", open:"2026-03-23",  design:"진의영", coding:"김동훈", photo:"사진",     photoCo:"박중원", photoDate:"",           url:"jinsimdental22.mycafe24.com/",        sslStart:"",           hostingStart:"2025-04-20", hostingInfo:"cafe24 / 진심스마트",          ftp:"jinsimd / jinsimd22!",                         domain:"jinsimdental.co.kr" },
  { id:"3",  name:"서울다온치과2",               status:"종료",       start:"2026-02-09", deadline:"2026-02-27", done:"2026-03-09", open:"2026-03-16",  design:"진의영", coding:"김동훈", photo:"사진+영상",photoCo:"박중원", photoDate:"2026-03-13", url:"seouldaon2.mycafe24.com/",            sslStart:"2025-05-10", hostingStart:"2025-05-10", hostingInfo:"cafe24",                  ftp:"seouldaon2 / daon!@2",                         domain:"seouldaon.com / 2027-01-10" },
  { id:"4",  name:"기존 타입 디벨롭",            status:"부류",       start:"2026-02-02", deadline:"",          done:"",           open:"",            design:"진의영", coding:"김동훈", photo:"",         photoCo:"",      photoDate:"",           url:"",                                    sslStart:"",           hostingStart:"",           hostingInfo:"",                        ftp:"",                                             domain:"" },
  { id:"5",  name:"금천연세세브란스치과 리뉴얼", status:"완료",       start:"2026-02-01", deadline:"",          done:"2026-02-28", open:"",            design:"외주",   coding:"외주",   photo:"사진",     photoCo:"박상준", photoDate:"2026-02-03", url:"",                                    sslStart:"",           hostingStart:"",           hostingInfo:"",                        ftp:"",                                             domain:"" },
  { id:"6",  name:"잠담치과 리뉴얼",             status:"완료",       start:"2026-01-29", deadline:"",          done:"2026-02-10", open:"",            design:"외주",   coding:"외주",   photo:"사진",     photoCo:"박상준", photoDate:"",           url:"",                                    sslStart:"",           hostingStart:"",           hostingInfo:"",                        ftp:"",                                             domain:"" },
  { id:"7",  name:"나란이덕치과 리뉴얼",         status:"전체피드백", start:"2026-01-01", deadline:"2026-01-21",done:"2026-02-07", open:"",            design:"",       coding:"외주",   photo:"",         photoCo:"",      photoDate:"",           url:"naranlee.mycafe24.com/",              sslStart:"",           hostingStart:"",           hostingInfo:"cafe24",                  ftp:"naranlee / lee!@1",                            domain:"naranlee.com" },
  { id:"8",  name:"진심스마트치과",              status:"디자인중",   start:"",           deadline:"",          done:"",           open:"",            design:"진의영", coding:"김동훈", photo:"",         photoCo:"",      photoDate:"",           url:"jinsimsmart.co.kr",                   sslStart:"",           hostingStart:"2025-12-01", hostingInfo:"cafe24 / jinsimd",        ftp:"jinsimd / smart!2",                            domain:"jinsimsmart.co.kr / 2027-05-01" },
  { id:"9",  name:"메디앤메디스케줄",            status:"종료",       start:"",           deadline:"",          done:"",           open:"",            design:"",       coding:"김동훈", photo:"",         photoCo:"",      photoDate:"",           url:"medischedule.co.kr/",                 sslStart:"2026-03-24", hostingStart:"2026-03-24", hostingInfo:"가비아",                  ftp:"medischedule / medi!@",                        domain:"medischedule.co.kr / 2027-03-24" },
  { id:"10", name:"케이플란트치과",              status:"종료",       start:"",           deadline:"",          done:"",           open:"",            design:"",       coding:"김동훈", photo:"",         photoCo:"",      photoDate:"",           url:"kplant2.mycafe24.com/",               sslStart:"",           hostingStart:"",           hostingInfo:"cafe24",                  ftp:"kplant2 / kplan!2",                            domain:"kplantdental.com" },
  { id:"11", name:"스토리플랜",                  status:"",           start:"",           deadline:"",          done:"",           open:"",            design:"",       coding:"",       photo:"",         photoCo:"",      photoDate:"",           url:"storypl",                             sslStart:"",           hostingStart:"",           hostingInfo:"",                        ftp:"",                                             domain:"" },
  { id:"12", name:"서울케이플란트치과(임시페이지)",status:"기획중",    start:"",           deadline:"2026-04-06",done:"",           open:"",            design:"",       coding:"",       photo:"",         photoCo:"",      photoDate:"",           url:"",                                    sslStart:"",           hostingStart:"",           hostingInfo:"",                        ftp:"",                                             domain:"" },
  { id:"13", name:"연세꿈꾸는치과(송도)",         status:"유지보수",   start:"",           deadline:"",          done:"",           open:"",            design:"",       coding:"김동훈", photo:"",         photoCo:"",      photoDate:"",           url:"yonseidreaming.com/index.php",        sslStart:"",           hostingStart:"2025-12-01", hostingInfo:"가비아",                  ftp:"yonseidream / dream!@",                        domain:"yonseidreaming.com / 2026-12-01" },
  { id:"14", name:"연세바른치과",                status:"대기중",     start:"",           deadline:"2026-04-11",done:"",           open:"",            design:"",       coding:"",       photo:"",         photoCo:"",      photoDate:"",           url:"yeonsebarunson2.mycafe24.com/",       sslStart:"",           hostingStart:"",           hostingInfo:"",                        ftp:"",                                             domain:"" },
  { id:"15", name:"파주 빛가람 플란트치과",       status:"유지보수",   start:"",           deadline:"",          done:"",           open:"",            design:"",       coding:"김동훈", photo:"",         photoCo:"",      photoDate:"",           url:"bitgaram-plant.com/",                 sslStart:"",           hostingStart:"",           hostingInfo:"카페24",                  ftp:"bitgaram / plant!@1",                          domain:"bitgaram-plant.com / 2027-08-15" },
  { id:"16", name:"더케이365치과",               status:"종료",       start:"",           deadline:"",          done:"",           open:"",            design:"",       coding:"김동훈", photo:"",         photoCo:"",      photoDate:"",           url:"",                                    sslStart:"",           hostingStart:"",           hostingInfo:"",                        ftp:"",                                             domain:"" },
  { id:"17", name:"서울상록수치과",              status:"대기중",     start:"",           deadline:"2026-04-27",done:"",           open:"",            design:"",       coding:"",       photo:"",         photoCo:"",      photoDate:"",           url:"",                                    sslStart:"",           hostingStart:"",           hostingInfo:"",                        ftp:"",                                             domain:"" },
  { id:"18", name:"산본중심치과",                status:"대기중",     start:"",           deadline:"2026-04-13",done:"",           open:"",            design:"",       coding:"",       photo:"",         photoCo:"",      photoDate:"",           url:"",                                    sslStart:"",           hostingStart:"",           hostingInfo:"",                        ftp:"",                                             domain:"" },
  { id:"19", name:"당신의치과",                  status:"코딩중",     start:"",           deadline:"2026-04-03",done:"",           open:"",            design:"",       coding:"",       photo:"",         photoCo:"",      photoDate:"",           url:"forudental2.mycafe24.com/index2.php", sslStart:"",          hostingStart:"",           hostingInfo:"cafe24",                  ftp:"forudental2 / foru!@2",                        domain:"forudental.com / 2026-09-01" },
  { id:"20", name:"스토리플랜 카페24",            status:"완료",       start:"",           deadline:"",          done:"",           open:"",            design:"",       coding:"",       photo:"",         photoCo:"",      photoDate:"",           url:"storyplan / !@tmxhff9218",            sslStart:"",           hostingStart:"",           hostingInfo:"",                        ftp:"",                                             domain:"" },
  { id:"21", name:"키즈앤패밀리치과 리뉴얼",      status:"대기중",     start:"2026-03-24", deadline:"",          done:"",           open:"",            design:"진의영", coding:"김동훈", photo:"사진+영상",photoCo:"박중원", photoDate:"2026-02-27", url:"kidsandfamily2.mycafe24.com/",        sslStart:"",           hostingStart:"",           hostingInfo:"cafe24 / kidsandfamily",  ftp:"kidsandfamily2 / kids!@2",                     domain:"kidsandfamily.co.kr / 2027-03-24" },
  { id:"22", name:"365편한일층치과",              status:"기획중",     start:"2026-03-31", deadline:"2026-04-08",done:"",           open:"",            design:"",       coding:"",       photo:"",         photoCo:"",      photoDate:"",           url:"firstfloor3652.mycafe24.com/index2.php",sslStart:"",         hostingStart:"",           hostingInfo:"",                        ftp:"",                                             domain:"" },
];

// ══════════════════════════════════════════════
//  상태 색상 맵
// ══════════════════════════════════════════════
const STATUS_COLORS = {
  "완료":"#34d399","종료":"#6b7280","부류":"#818cf8","대기중":"#fbbf24",
  "유지보수":"#60a5fa","기획중":"#f472b6","코딩중":"#fb923c",
  "디자인중":"#a78bfa","전체피드백":"#f87171"
};

function statusClass(s) {
  const map = { "완료":"완료","종료":"종료","부류":"부류","대기중":"대기중",
                "유지보수":"유지보수","기획중":"기획중","코딩중":"코딩중",
                "디자인중":"디자인중","전체피드백":"전체피드백" };
  return map[s] ? `badge-status s-${map[s]}` : "badge-status s-종료";
}

function avatarBg(status) {
  return STATUS_COLORS[status] || "#555a7a";
}

// ══════════════════════════════════════════════
//  날짜 유틸
// ══════════════════════════════════════════════
function add365(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  if (isNaN(d)) return "";
  d.setDate(d.getDate() + 365);
  return d.toISOString().slice(0, 10);
}

function daysUntil(dateStr) {
  if (!dateStr || dateStr.length < 8) return null;
  const target = new Date(dateStr);
  if (isNaN(target)) return null;
  const today = new Date(); today.setHours(0,0,0,0);
  return Math.ceil((target - today) / 86400000);
}

function expiryText(dateStr) {
  if (!dateStr || dateStr.length < 8) return `<span style="color:var(--text3)">-</span>`;
  const d = daysUntil(dateStr);
  if (d === null) return `<span>${dateStr}</span>`;
  if (d <= 7)  return `<span class="expiry-danger">🔴 ${dateStr} (D-${d})</span>`;
  if (d <= 30) return `<span class="expiry-warn">🟡 ${dateStr} (D-${d})</span>`;
  return `<span>${dateStr}</span>`;
}

function val(v) {
  return v ? v : `<span class="empty">-</span>`;
}

function showToast(msg, icon = "✅") {
  const t = document.getElementById("toast");
  t.textContent = `${icon} ${msg}`;
  t.classList.add("show");
  setTimeout(() => t.classList.remove("show"), 2500);
}

// ══════════════════════════════════════════════
//  데이터 로드
// ══════════════════════════════════════════════
let allData = [];

async function loadData() {
  if (USE_DUMMY) return JSON.parse(JSON.stringify(DUMMY_DATA)); // deep copy
  let results = [], cursor;
  do {
    const res = await fetch(`https://api.notion.com/v1/databases/${DATABASE_ID}/query`, {
      method: "POST",
      headers: {
        "Authorization": `Bearer ${NOTION_API_KEY}`,
        "Notion-Version": NOTION_VERSION,
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ start_cursor: cursor, page_size: 100 })
    });
    const data = await res.json();
    results.push(...data.results.map(parseNotionPage));
    cursor = data.has_more ? data.next_cursor : null;
  } while (cursor);
  return results;
}

function parseNotionPage(page) {
  const p = page.properties;
  return {
    id:          page.id,
    name:        p["병원명"]?.title?.[0]?.plain_text ?? "",
    status:      p["상태"]?.status?.name ?? p["상태"]?.select?.name ?? "",
    start:       p["시작일"]?.date?.start ?? "",
    deadline:    p["1차 마감일"]?.date?.start ?? "",
    done:        p["완료일"]?.date?.start ?? "",
    open:        p["개원날짜"]?.date?.start ?? "",
    design:      p["디자인"]?.people?.[0]?.name ?? p["디자인"]?.select?.name ?? "",
    coding:      p["코딩"]?.people?.[0]?.name ?? p["코딩"]?.select?.name ?? "",
    photo:       p["촬영"]?.select?.name ?? p["촬영"]?.multi_select?.[0]?.name ?? "",
    photoCo:     p["촬영업체"]?.people?.[0]?.name ?? p["촬영업체"]?.select?.name ?? "",
    photoDate:   p["촬영날짜"]?.date?.start ?? "",
    url:         p["웹사이트 URL"]?.url ?? p["웹사이트 URL"]?.rich_text?.[0]?.plain_text ?? "",
    sslStart:    p["보안인증서 만료일자"]?.date?.start ?? "",
    hostingStart:p["호스팅 만료일자"]?.date?.start ?? "",
    hostingInfo: p["호스팅 정보"]?.rich_text?.[0]?.plain_text ?? "",
    ftp:         p["FTP 정보"]?.rich_text?.[0]?.plain_text ?? "",
    domain:      p["도메인 정보"]?.rich_text?.[0]?.plain_text ?? "",
  };
}

// ══════════════════════════════════════════════
//  탭 전환
// ══════════════════════════════════════════════
const tabTitles = { dashboard:"대시보드", list:"사이트 목록", register:"신규 등록", alerts:"만료 알림", detail:"상세 정보" };
let prevTab = "dashboard";

function switchTab(tab, updateNav = true) {
  document.querySelectorAll(".tab-panel").forEach(p => p.classList.remove("active"));
  document.getElementById(`tab-${tab}`).classList.add("active");
  document.getElementById("topTitle").textContent = tabTitles[tab] || tab;

  const backBtn = document.getElementById("backBtn");
  const topSep  = document.getElementById("topSep");
  const topSub  = document.getElementById("topSub");

  if (tab === "detail") {
    backBtn.style.display = "block";
    topSep.style.display  = "none";
    topSub.style.display  = "none";
  } else {
    backBtn.style.display = "none";
    topSep.style.display  = "";
    topSub.style.display  = "";
    prevTab = tab;
  }

  if (updateNav) {
    document.querySelectorAll(".nav-btn[data-tab]").forEach(b => {
      b.classList.toggle("active", b.dataset.tab === tab);
    });
  }
}

document.querySelectorAll(".nav-btn[data-tab]").forEach(btn => {
  btn.addEventListener("click", () => switchTab(btn.dataset.tab));
});

document.getElementById("backBtn").addEventListener("click", () => {
  switchTab(prevTab);
});

// ══════════════════════════════════════════════
//  대시보드
// ══════════════════════════════════════════════
function renderDashboard(data) {
  const activeStatuses = ["기획중","디자인중","코딩중","부류","전체피드백"];
  const active   = data.filter(d => activeStatuses.includes(d.status));
  const waiting  = data.filter(d => d.status === "대기중");
  const expiring = data.filter(d => {
    const hd = daysUntil(add365(d.hostingStart));
    const sd = daysUntil(add365(d.sslStart));
    return (hd !== null && hd <= 30) || (sd !== null && sd <= 30);
  });

  document.getElementById("stat-total").textContent   = data.length;
  document.getElementById("stat-active").textContent  = active.length;
  document.getElementById("stat-waiting").textContent = waiting.length;
  document.getElementById("stat-expiry").textContent  = expiring.length;

  if (expiring.length > 0) {
    document.getElementById("alertNavBtn").innerHTML = `🔔<span class="badge"></span>`;
  }

  const showStatuses = ["기획중","디자인중","코딩중","부류","전체피드백","대기중","유지보수"];
  const groups = {};
  showStatuses.forEach(s => { groups[s] = data.filter(d => d.status === s); });

  const board = document.getElementById("kanban-board");
  board.innerHTML = showStatuses
    .filter(s => groups[s].length > 0)
    .map(s => `
      <div class="kanban-col">
        <div class="kanban-col-title">
          <span class="dot" style="background:${STATUS_COLORS[s]||"#555"}"></span>
          ${s} <span style="color:var(--text3);font-weight:400;margin-left:2px">${groups[s].length}</span>
        </div>
        ${groups[s].map(d => `
          <div class="kanban-item" data-id="${d.id}">
            <div class="k-name">${d.name}</div>
            <div class="k-meta">${d.deadline ? "마감 " + d.deadline : d.coding || (d.design || "")}</div>
          </div>
        `).join("")}
      </div>
    `).join("");

  // 칸반 카드 클릭 → 상세
  board.querySelectorAll(".kanban-item").forEach(el => {
    el.addEventListener("click", () => {
      const item = allData.find(d => d.id === el.dataset.id);
      if (item) openDetail(item);
    });
  });
}

// ══════════════════════════════════════════════
//  카드 목록
// ══════════════════════════════════════════════
function renderCards(data) {
  const grid = document.getElementById("cardGrid");
  if (data.length === 0) {
    grid.innerHTML = `<div class="empty"><div class="e-icon">🔍</div>검색 결과가 없습니다</div>`;
    return;
  }

  grid.innerHTML = data.map(d => {
    const firstChar = d.name.charAt(0);
    const color     = avatarBg(d.status);
    const openStr   = d.open   ? `개원 ${d.open}` : "";
    const deadStr   = d.deadline ? `마감 ${d.deadline}` : "";
    const metaStr   = openStr || deadStr || (d.url ? d.url.replace(/\/$/, "").substring(0,28) : "");
    return `
      <div class="site-card" data-id="${d.id}">
        <div class="site-avatar" style="background:${color}22;border:1.5px solid ${color}44">
          <span style="color:${color}">${firstChar}</span>
        </div>
        <div class="site-card-body">
          <div class="site-card-name" title="${d.name}">${d.name}</div>
          <div class="site-card-meta">
            <span class="${statusClass(d.status)}">${d.status || "미분류"}</span>
            ${metaStr ? `<span class="site-card-open">${metaStr}</span>` : ""}
          </div>
        </div>
        <div class="site-card-arrow">›</div>
      </div>
    `;
  }).join("");

  grid.querySelectorAll(".site-card").forEach(el => {
    el.addEventListener("click", () => {
      const item = allData.find(d => d.id === el.dataset.id);
      if (item) openDetail(item);
    });
  });
}

// ══════════════════════════════════════════════
//  상세 패널
// ══════════════════════════════════════════════
let currentDetailId = null;
let isEditMode = false;

function openDetail(d) {
  currentDetailId = d.id;
  isEditMode = false;
  renderDetail(d, false);
  prevTab = document.querySelector(".nav-btn.active")?.dataset.tab || "list";
  switchTab("detail", false);
}

function renderDetail(d, editMode) {
  const color     = avatarBg(d.status);
  const firstChar = d.name.charAt(0);

  // ── 헤더
  document.getElementById("detailHeader").innerHTML = `
    <div class="detail-avatar" style="background:${color}22;border:1.5px solid ${color}55">
      <span style="color:${color}">${firstChar}</span>
    </div>
    <div class="detail-title">
      <h2 title="${d.name}">${d.name}</h2>
      <span class="${statusClass(d.status)}">${d.status || "미분류"}</span>
    </div>
    <div class="detail-actions">
      ${editMode
        ? `<button class="btn btn-primary btn-sm" id="detailSaveBtn">💾 저장</button>
           <button class="btn btn-ghost btn-sm" id="detailCancelBtn">취소</button>`
        : `<button class="btn-seo" id="detailSeoBtn">🔍 SEO</button>
           <button class="btn btn-ghost btn-sm" id="detailEditBtn">✏️ 수정</button>
           <button class="btn btn-danger btn-sm" id="detailDeleteBtn">🗑️</button>`
      }
    </div>
  `;

  // ── 바디
  const STATUSES = ["기획중","디자인중","코딩중","대기중","부류","유지보수","전체피드백","완료","종료"];
  const DESIGNS  = ["","진의영","외주"];
  const CODINGS  = ["","김동훈","외주"];
  const PHOTOS   = ["","사진","사진+영상"];
  const PHOTO_COS= ["","박중원","장영진","박상준"];

  function field(label, key, type = "text", options = null, fullWidth = false) {
    const v = d[key] || "";
    const cls = fullWidth ? "detail-field full" : "detail-field";
    if (editMode) {
      if (options) {
        return `<div class="${cls}">
          <div class="detail-field-label">${label}</div>
          <select class="edit-select" data-key="${key}">
            ${options.map(o => `<option value="${o}" ${o===v?"selected":""}>${o||"선택"}</option>`).join("")}
          </select>
        </div>`;
      }
      return `<div class="${cls}">
        <div class="detail-field-label">${label}</div>
        <input class="edit-input" type="${type}" data-key="${key}" value="${v}" placeholder="${label}">
      </div>`;
    }
    // view mode
    let display;
    if (key === "url" && v) {
      const href = v.startsWith("http") ? v : `https://${v}`;
      display = `<a href="${href}" target="_blank">${v}</a>`;
    } else {
      display = v ? `<span>${v}</span>` : `<span class="empty">-</span>`;
    }
    return `<div class="${cls}">
      <div class="detail-field-label">${label}</div>
      <div class="detail-field-value">${display}</div>
    </div>`;
  }

  // SSL/호스팅 전용: 시작일 + 자동 만료일
  function expiryField(label, startKey, fullWidth = false) {
    const startVal  = d[startKey] || "";
    const expiryVal = add365(startVal);
    const cls = fullWidth ? "detail-field full" : "detail-field";
    if (editMode) {
      return `<div class="${cls}">
        <div class="detail-field-label">${label} 시작일</div>
        <input class="edit-input" type="date" data-key="${startKey}" value="${startVal}">
        <div class="detail-field-label" style="margin-top:4px">↳ 만료일 (자동)</div>
        <div class="detail-field-value" style="font-size:11px;color:var(--accent)">${expiryVal || "-"}</div>
      </div>`;
    }
    const expiryDisplay = expiryVal ? expiryText(expiryVal) : `<span class="empty">-</span>`;
    return `<div class="${cls}">
      <div class="detail-field-label">${label} 시작일</div>
      <div class="detail-field-value">${startVal || `<span class="empty">-</span>`}</div>
      <div class="detail-field-label" style="margin-top:4px">↳ 만료일 (자동)</div>
      <div class="detail-field-value">${expiryDisplay}</div>
    </div>`;
  }

  document.getElementById("detailBody").innerHTML = `
    <!-- 일정 -->
    <div class="detail-section">
      <div class="detail-section-title">📅 일정</div>
      <div class="detail-fields">
        ${field("시작일",    "start",    "date")}
        ${field("1차 마감일","deadline", "date")}
        ${field("완료일",    "done",     "date")}
        ${field("개원날짜",  "open",     "date")}
      </div>
    </div>
    <!-- 담당 -->
    <div class="detail-section">
      <div class="detail-section-title">👤 담당</div>
      <div class="detail-fields">
        ${field("디자인", "design",  "text", DESIGNS)}
        ${field("코딩",   "coding",  "text", CODINGS)}
      </div>
    </div>
    <!-- 촬영 -->
    <div class="detail-section">
      <div class="detail-section-title">📷 촬영</div>
      <div class="detail-fields">
        ${field("촬영",     "photo",     "text", PHOTOS)}
        ${field("촬영업체", "photoCo",   "text", PHOTO_COS)}
        ${field("촬영날짜", "photoDate", "date")}
      </div>
    </div>
    <!-- 웹사이트 -->
    <div class="detail-section">
      <div class="detail-section-title">🌐 웹사이트</div>
      <div class="detail-fields">
        ${field("URL", "url", "text", null, true)}
        ${expiryField("SSL 인증서", "sslStart")}
        ${expiryField("호스팅",     "hostingStart")}
      </div>
    </div>
    <!-- 서버 정보 -->
    <div class="detail-section">
      <div class="detail-section-title">🖥️ 서버 정보</div>
      <div class="detail-fields">
        ${field("호스팅 정보", "hostingInfo", "text", null, true)}
        ${field("FTP 정보",    "ftp",         "text", null, true)}
        ${field("도메인 정보", "domain",      "text", null, true)}
      </div>
    </div>
  `;

  // ── 이벤트 바인딩
  if (editMode) {
    document.getElementById("detailSaveBtn").addEventListener("click", () => saveDetail(d));
    document.getElementById("detailCancelBtn").addEventListener("click", () => {
      isEditMode = false;
      renderDetail(d, false);
    });
  } else {
    document.getElementById("detailSeoBtn").addEventListener("click", () => openSeoModal(d));
    document.getElementById("detailEditBtn").addEventListener("click", () => {
      isEditMode = true;
      renderDetail(d, true);
    });
    document.getElementById("detailDeleteBtn").addEventListener("click", () => {
      if (confirm(`"${d.name}"을 삭제할까요?`)) {
        allData = allData.filter(x => x.id !== d.id);
        renderDashboard(allData);
        renderCards(allData);
        renderAlerts(allData);
        showToast(`"${d.name}" 삭제됨`, "🗑️");
        switchTab(prevTab);
      }
    });
  }
}

function saveDetail(original) {
  const inputs = document.querySelectorAll("#detailBody .edit-input, #detailBody .edit-select");
  const updated = { ...original };
  inputs.forEach(el => { updated[el.dataset.key] = el.value; });

  const idx = allData.findIndex(x => x.id === original.id);
  if (idx !== -1) allData[idx] = updated;

  renderDashboard(allData);
  renderCards(allData);
  renderAlerts(allData);
  isEditMode = false;
  renderDetail(updated, false);
  showToast("수정 완료!", "✅");
}

// ══════════════════════════════════════════════
//  알림
// ══════════════════════════════════════════════
function renderAlerts(data) {
  const alerts = [];
  data.forEach(d => {
    const hostingExpiry = add365(d.hostingStart);
    const sslExpiry     = add365(d.sslStart);
    const hd = daysUntil(hostingExpiry);
    const sd = daysUntil(sslExpiry);
    if (hd !== null && hd <= 30) alerts.push({ name:d.name, type:"호스팅",     days:hd, date:hostingExpiry, id:d.id });
    if (sd !== null && sd <= 30) alerts.push({ name:d.name, type:"SSL 인증서", days:sd, date:sslExpiry,     id:d.id });
  });

  const list = document.getElementById("alertList");
  if (alerts.length === 0) {
    list.innerHTML = `<div class="empty"><div class="e-icon">✅</div>30일 이내 만료 항목이 없습니다</div>`;
    return;
  }
  alerts.sort((a,b) => a.days - b.days);
  list.innerHTML = alerts.map(a => `
    <div class="alert-card ${a.days <= 7 ? "danger" : "warning"}" data-id="${a.id}" style="cursor:pointer">
      <div class="alert-icon">${a.days <= 7 ? "🚨" : "⚠️"}</div>
      <div class="alert-body">
        <div class="a-name">${a.name}</div>
        <div class="a-detail">${a.type} 만료: ${a.date}</div>
      </div>
      <div class="a-days ${a.days <= 7 ? "alert-days-danger" : "alert-days-warning"}">D-${a.days}</div>
    </div>
  `).join("");

  list.querySelectorAll(".alert-card").forEach(el => {
    el.addEventListener("click", () => {
      const item = allData.find(d => d.id === el.dataset.id);
      if (item) openDetail(item);
    });
  });
}

// ══════════════════════════════════════════════
//  필터/검색
// ══════════════════════════════════════════════
function applyFilter() {
  const q      = document.getElementById("searchInput").value.toLowerCase();
  const status = document.getElementById("statusFilter").value;
  const filtered = allData.filter(d => {
    const matchQ = !q || d.name.toLowerCase().includes(q) || (d.url||"").toLowerCase().includes(q);
    const matchS = !status || d.status === status;
    return matchQ && matchS;
  });
  renderCards(filtered);
}

document.getElementById("searchInput").addEventListener("input", applyFilter);
document.getElementById("statusFilter").addEventListener("change", applyFilter);

// ══════════════════════════════════════════════
//  신규 등록
// ══════════════════════════════════════════════
const FORM_TEXT_IDS   = ["f-name","f-start","f-deadline","f-done","f-open","f-url","f-ssl","f-hosting","f-photo-date","f-hosting-info","f-ftp","f-domain"];
const FORM_SELECT_IDS = ["f-status","f-design","f-coding","f-photo","f-photo-co"];

// 시작일 → 만료일 힌트 자동 표시
["f-ssl","f-hosting"].forEach(id => {
  const hintId = id + "-hint";
  document.getElementById(id).addEventListener("change", e => {
    const expiry = add365(e.target.value);
    const hint = document.getElementById(hintId);
    if (hint) hint.textContent = expiry ? `만료일 자동계산: ${expiry}` : "";
  });
});

document.getElementById("f-reset").addEventListener("click", () => {
  FORM_TEXT_IDS.forEach(id => document.getElementById(id).value = "");
  FORM_SELECT_IDS.forEach(id => document.getElementById(id).selectedIndex = 0);
});

document.getElementById("f-save").addEventListener("click", async () => {
  const name = document.getElementById("f-name").value.trim();
  if (!name) { showToast("사이트명을 입력해주세요", "⚠️"); return; }

  const newEntry = {
    id:          String(Date.now()),
    name,
    status:      document.getElementById("f-status").value,
    start:       document.getElementById("f-start").value,
    deadline:    document.getElementById("f-deadline").value,
    done:        document.getElementById("f-done").value,
    open:        document.getElementById("f-open").value,
    design:      document.getElementById("f-design").value,
    coding:      document.getElementById("f-coding").value,
    photo:       document.getElementById("f-photo").value,
    photoCo:     document.getElementById("f-photo-co").value,
    photoDate:   document.getElementById("f-photo-date").value,
    url:         document.getElementById("f-url").value,
    sslStart:    document.getElementById("f-ssl").value,
    hostingStart:document.getElementById("f-hosting").value,
    hostingInfo: document.getElementById("f-hosting-info").value,
    ftp:         document.getElementById("f-ftp").value,
    domain:      document.getElementById("f-domain").value,
  };

  allData.unshift(newEntry);
  renderDashboard(allData);
  renderCards(allData);
  renderAlerts(allData);
  showToast(`"${name}" 등록 완료!`);
  document.getElementById("f-reset").click();
  switchTab("list");
});

// ══════════════════════════════════════════════
//  초기 로드
// ══════════════════════════════════════════════
(async () => {
  allData = await loadData();
  renderDashboard(allData);
  renderCards(allData);
  renderAlerts(allData);
})();

// ══════════════════════════════════════════════
//  오늘 업무 (Today Tasks)
//  - chrome.storage.sync 로 같은 Google 계정 기기 간 공유
//  - 매일 09:00 에 자동 초기화
// ══════════════════════════════════════════════
const TASK_KEY      = "todayTasks";
const TASK_DATE_KEY = "todayTasksDate";

function todayStr() {
  return new Date().toISOString().slice(0, 10); // "YYYY-MM-DD"
}

// storage 래퍼 (chrome.storage.sync 없으면 localStorage fallback)
const taskStorage = {
  get(keys) {
    return new Promise(resolve => {
      if (typeof chrome !== "undefined" && chrome.storage) {
        chrome.storage.sync.get(keys, resolve);
      } else {
        const result = {};
        keys.forEach(k => { const v = localStorage.getItem(k); result[k] = v ? JSON.parse(v) : undefined; });
        resolve(result);
      }
    });
  },
  set(items) {
    return new Promise(resolve => {
      if (typeof chrome !== "undefined" && chrome.storage) {
        chrome.storage.sync.set(items, resolve);
      } else {
        Object.entries(items).forEach(([k, v]) => localStorage.setItem(k, JSON.stringify(v)));
        resolve();
      }
    });
  }
};

let todayTasks = [];   // [{ id, text, done, doneAt, doneBy }]

async function loadTasks() {
  const data = await taskStorage.get([TASK_KEY, TASK_DATE_KEY]);
  const savedDate = data[TASK_DATE_KEY] || "";
  const today     = todayStr();

  if (savedDate !== today) {
    // 날짜가 다르면 초기화
    todayTasks = [];
    await taskStorage.set({ [TASK_KEY]: [], [TASK_DATE_KEY]: today });
  } else {
    todayTasks = data[TASK_KEY] || [];
  }

  renderTasks();
  scheduleDailyReset();
}

async function saveTasks() {
  await taskStorage.set({ [TASK_KEY]: todayTasks, [TASK_DATE_KEY]: todayStr() });
}

// 09:00 초기화 스케줄러
function scheduleDailyReset() {
  const now    = new Date();
  const reset  = new Date(now);
  reset.setHours(9, 0, 0, 0);
  if (now >= reset) reset.setDate(reset.getDate() + 1); // 이미 9시 지났으면 내일
  const msUntil = reset - now;
  setTimeout(async () => {
    todayTasks = [];
    await taskStorage.set({ [TASK_KEY]: [], [TASK_DATE_KEY]: todayStr() });
    renderTasks();
    showCompleteFloat("📋 오늘 업무 리스트가 초기화되었습니다", "#5a6080");
    scheduleDailyReset(); // 다음 날 재등록
  }, msUntil);
}

// 렌더링
function renderTasks() {
  const list = document.getElementById("taskList");
  if (!list) return;

  // 날짜 표시
  const dateEl = document.getElementById("todayDate");
  if (dateEl) {
    const now = new Date();
    dateEl.textContent = `${now.getMonth()+1}월 ${now.getDate()}일 (${["일","월","화","수","목","금","토"][now.getDay()]})`;
  }

  if (todayTasks.length === 0) {
    list.innerHTML = `<div class="task-empty">오늘 업무가 없습니다. ＋ 버튼으로 추가하세요.</div>`;
    return;
  }

  list.innerHTML = todayTasks.map(t => `
    <div class="task-item ${t.done ? "done" : ""}" data-id="${t.id}">
      <div class="task-check ${t.done ? "checked" : ""}" data-id="${t.id}" title="${t.done ? "완료 취소" : "완료 처리"}">
        ${t.done ? "✓" : ""}
      </div>
      <div class="task-text">${t.text}</div>
      ${t.done
        ? `<span class="task-done-badge">완료</span>
           ${t.doneBy ? `<span class="task-done-by">${t.doneBy}</span>` : ""}`
        : ""}
      <button class="task-delete" data-id="${t.id}" title="삭제">✕</button>
    </div>
  `).join("");

  // 체크 이벤트
  list.querySelectorAll(".task-check").forEach(el => {
    el.addEventListener("click", () => toggleTask(el.dataset.id));
  });
  // 삭제 이벤트
  list.querySelectorAll(".task-delete").forEach(el => {
    el.addEventListener("click", e => {
      e.stopPropagation();
      deleteTask(el.dataset.id);
    });
  });
}

async function addTask(text) {
  if (!text.trim()) return;
  todayTasks.push({
    id:     String(Date.now()),
    text:   text.trim(),
    done:   false,
    doneAt: "",
    doneBy: ""
  });
  await saveTasks();
  renderTasks();
}

async function toggleTask(id) {
  const task = todayTasks.find(t => t.id === id);
  if (!task) return;
  task.done   = !task.done;
  task.doneAt = task.done ? new Date().toLocaleTimeString("ko-KR", { hour:"2-digit", minute:"2-digit" }) : "";
  task.doneBy = task.done ? "나" : "";  // 실제 배포 시 사용자명으로 교체

  await saveTasks();
  renderTasks();

  if (task.done) {
    showCompleteFloat(`✅ "${task.text}" 완료!`);
    triggerNotification(task.text);
  }
}

async function deleteTask(id) {
  todayTasks = todayTasks.filter(t => t.id !== id);
  await saveTasks();
  renderTasks();
}

// 완료 플로팅 팝업
function showCompleteFloat(msg, color = "#10b981") {
  const el = document.getElementById("completeFloat");
  if (!el) return;
  el.textContent = msg;
  el.style.background = color === "#10b981" ? "#1a1d35" : color;
  el.classList.add("show");
  setTimeout(() => el.classList.remove("show"), 2800);
}

// Chrome 알림 (같은 Google 계정 sync 기기 전체에 표시)
function triggerNotification(taskText) {
  if (typeof chrome === "undefined" || !chrome.notifications) return;
  chrome.notifications.create(`task_${Date.now()}`, {
    type:    "basic",
    iconUrl: "icon.png",
    title:   "✅ 업무 완료",
    message: `${taskText} 완료되었습니다!`,
    priority: 1
  });
}

// ── UI 이벤트 바인딩
document.addEventListener("DOMContentLoaded", () => {
  // 추가 버튼 토글
  const btnOpen = document.getElementById("btnOpenTaskAdd");
  const wrap    = document.getElementById("taskInputWrap");
  const input   = document.getElementById("taskInput");
  const btnAdd  = document.getElementById("btnAddTask");

  if (btnOpen) {
    btnOpen.addEventListener("click", () => {
      const isOpen = wrap.style.display !== "none";
      wrap.style.display = isOpen ? "none" : "flex";
      if (!isOpen) setTimeout(() => input.focus(), 50);
    });
  }

  if (btnAdd) {
    btnAdd.addEventListener("click", async () => {
      await addTask(input.value);
      input.value = "";
      wrap.style.display = "none";
    });
  }

  if (input) {
    input.addEventListener("keydown", async e => {
      if (e.key === "Enter") {
        await addTask(input.value);
        input.value = "";
        wrap.style.display = "none";
      }
      if (e.key === "Escape") {
        wrap.style.display = "none";
        input.value = "";
      }
    });
  }
});

// 폴링: 다른 기기에서 완료된 항목 감지 (5초마다)
setInterval(async () => {
  const data = await taskStorage.get([TASK_KEY, TASK_DATE_KEY]);
  if (data[TASK_DATE_KEY] !== todayStr()) return;
  const remoteTasks = data[TASK_KEY] || [];

  // 새로 완료된 항목 감지 → 플로팅 알림
  remoteTasks.forEach(rt => {
    const local = todayTasks.find(lt => lt.id === rt.id);
    if (rt.done && local && !local.done) {
      showCompleteFloat(`✅ "${rt.text}" 완료!`);
    }
  });

  todayTasks = remoteTasks;
  renderTasks();
}, 5000);

// 초기 로드
loadTasks();

// ══════════════════════════════════════════════
//  SEO Modal
// ══════════════════════════════════════════════

// Naver 키 저장/로드 (chrome.storage.local)
async function loadNaverKeys(projectId) {
  return new Promise(resolve => {
    const cb = data => resolve((data["naver_keys"] || {})[projectId] || { clientId:"", clientSecret:"" });
    if (typeof chrome !== "undefined" && chrome.storage) {
      chrome.storage.local.get(["naver_keys"], cb);
    } else {
      const raw = localStorage.getItem("naver_keys");
      cb(raw ? JSON.parse(raw) : {});
    }
  });
}

async function saveNaverKeys(projectId, clientId, clientSecret) {
  return new Promise(resolve => {
    const finish = existing => {
      const updated = { ...(existing["naver_keys"] || {}), [projectId]: { clientId, clientSecret } };
      if (typeof chrome !== "undefined" && chrome.storage) {
        chrome.storage.local.set({ naver_keys: updated }, resolve);
      } else {
        localStorage.setItem("naver_keys", JSON.stringify(updated));
        resolve();
      }
    };
    if (typeof chrome !== "undefined" && chrome.storage) {
      chrome.storage.local.get(["naver_keys"], finish);
    } else {
      const raw = localStorage.getItem("naver_keys");
      finish(raw ? JSON.parse(raw) : {});
    }
  });
}

// URL 정규화 (https:// 붙이기, 끝 슬래시 제거)
function normalizeUrl(url) {
  if (!url) return null;
  let u = url.trim().replace(/\/$/, "");
  if (!u.startsWith("http")) u = "https://" + u;
  try { return new URL(u).origin; } catch { return null; }
}

// robots.txt 체크
async function checkRobots(baseUrl) {
  const url = baseUrl + "/robots.txt";
  try {
    const res = await fetch(url, { signal: AbortSignal.timeout(6000) });
    if (!res.ok) return { status:"red", tag:"없음", detail:`HTTP ${res.status}` };
    const text = await res.text();
    const lower = text.toLowerCase();
    // 내용 유효성 검사
    if (!lower.includes("user-agent")) {
      return { status:"yellow", tag:"불완전", detail:"User-agent 지시자가 없습니다" };
    }
    // Disallow: / 로 전체 차단하면 경고
    if (/disallow\s*:\s*\/\s*\n/i.test(text) && !text.includes("Disallow: /admin")) {
      return { status:"yellow", tag:"주의", detail:"Disallow: / — 검색엔진 전체 차단 감지" };
    }
    const sitemapLine = text.match(/sitemap\s*:\s*(.+)/i);
    const detail = sitemapLine
      ? `Sitemap 경로 지정됨: ${sitemapLine[1].trim()}`
      : "정상 (Sitemap 경로 미지정)";
    return { status:"green", tag:"정상", detail };
  } catch (e) {
    return { status:"red", tag:"오류", detail: e.name === "TimeoutError" ? "요청 시간 초과" : "연결 실패" };
  }
}

// sitemap.xml 체크
async function checkSitemap(baseUrl) {
  // sitemap.xml 먼저, 없으면 sitemap_index.xml도 시도
  const candidates = ["/sitemap.xml", "/sitemap_index.xml", "/sitemap-index.xml"];
  for (const path of candidates) {
    try {
      const res = await fetch(baseUrl + path, { signal: AbortSignal.timeout(6000) });
      if (!res.ok) continue;
      const text = await res.text();
      const lower = text.toLowerCase();
      if (!lower.includes("<url") && !lower.includes("<sitemap")) {
        return { status:"yellow", tag:"불완전", detail:`${path} — 유효한 URL 항목 없음` };
      }
      // URL 개수 추출
      const count = (text.match(/<url>/gi) || []).length;
      const detail = count > 0
        ? `${path} — URL ${count}개 등록됨`
        : `${path} — sitemapindex 형식`;
      return { status:"green", tag:"정상", detail };
    } catch { continue; }
  }
  return { status:"red", tag:"없음", detail:"sitemap.xml을 찾을 수 없습니다" };
}

// SEO 모달 렌더
function renderSeoChecks(checks) {
  return checks.map(c => `
    <div class="seo-check-row">
      <div class="seo-light ${c.status}"></div>
      <div class="seo-check-info">
        <div class="seo-check-label">${c.label}</div>
        <div class="seo-check-detail">${c.detail}</div>
      </div>
      <span class="seo-check-tag ${c.status}">${c.tag}</span>
    </div>
  `).join("");
}

async function openSeoModal(d) {
  const overlay = document.getElementById("seoModal");
  const body    = document.getElementById("seoModalBody");
  const title   = document.getElementById("seoModalTitle");

  title.textContent = `🔍 SEO — ${d.name}`;
  body.innerHTML = `<div class="seo-loading"><div class="spinner"></div>분석 중...</div>`;
  overlay.classList.add("open");

  const baseUrl = normalizeUrl(d.url);
  const keys    = await loadNaverKeys(d.id);

  if (!baseUrl) {
    body.innerHTML = `
      <p style="color:var(--danger);font-size:12px;text-align:center;padding:16px">
        ⚠️ 등록된 URL이 없습니다.<br>상세 정보에서 URL을 먼저 입력해주세요.
      </p>
      ${naverKeySection(d.id, keys)}
    `;
    bindNaverKeySave(d.id);
    return;
  }

  // 병렬 체크
  const [robots, sitemap] = await Promise.all([
    checkRobots(baseUrl),
    checkSitemap(baseUrl)
  ]);

  body.innerHTML = `
    <!-- 크롤링 설정 -->
    <div>
      <div class="seo-section-title">🤖 크롤링 설정</div>
      ${renderSeoChecks([
        { label:"robots.txt",   ...robots  },
        { label:"sitemap.xml",  ...sitemap },
      ])}
    </div>

    <div class="seo-divider"></div>

    <!-- 네이버 서치어드바이저 -->
    <div>
      <div class="seo-section-title">🟢 네이버 서치어드바이저</div>
      ${naverKeySection(d.id, keys)}
    </div>
  `;

  bindNaverKeySave(d.id);
}

function naverKeySection(projectId, keys) {
  const hasSaved = keys.clientId && keys.clientSecret;
  return `
    <div class="seo-key-grid">
      <div class="seo-key-row">
        <div class="seo-key-label">Client ID</div>
        <input class="seo-key-input" id="naverClientId" type="text"
          placeholder="네이버 Client ID"
          value="${keys.clientId || ""}">
      </div>
      <div class="seo-key-row">
        <div class="seo-key-label">Client Secret</div>
        <input class="seo-key-input" id="naverClientSecret" type="password"
          placeholder="네이버 Client Secret"
          value="${keys.clientSecret || ""}">
      </div>
      <div style="display:flex;gap:6px;align-items:center;margin-top:2px">
        <button class="btn btn-primary btn-sm" id="naverSaveBtn">💾 키 저장</button>
        ${hasSaved ? `<span class="seo-key-saved">✓ 저장된 키 있음</span>` : ""}
      </div>
      ${hasSaved ? `
        <div style="margin-top:4px">
          <button class="btn btn-ghost btn-sm" id="naverQueryBtn">📊 순위 조회</button>
        </div>
        <div id="naverResult" style="margin-top:8px"></div>
      ` : `
        <p style="font-size:10px;color:var(--text3);margin-top:4px">
          키를 저장하면 검색 순위 조회가 가능합니다
        </p>
      `}
    </div>
  `;
}

function bindNaverKeySave(projectId) {
  const saveBtn  = document.getElementById("naverSaveBtn");
  const queryBtn = document.getElementById("naverQueryBtn");

  if (saveBtn) {
    saveBtn.addEventListener("click", async () => {
      const id  = document.getElementById("naverClientId").value.trim();
      const sec = document.getElementById("naverClientSecret").value.trim();
      if (!id || !sec) { showToast("Client ID와 Secret을 모두 입력해주세요", "⚠️"); return; }
      await saveNaverKeys(projectId, id, sec);
      showToast("API 키 저장 완료!", "✅");
      // 저장 후 UI 갱신
      const d = allData.find(x => x.id === projectId);
      if (d) openSeoModal(d);
    });
  }

  if (queryBtn) {
    queryBtn.addEventListener("click", async () => {
      const resultEl = document.getElementById("naverResult");
      if (!resultEl) return;
      resultEl.innerHTML = `<div class="seo-loading"><div class="spinner"></div>조회 중...</div>`;
      const keys = await loadNaverKeys(projectId);
      await queryNaverRank(keys, resultEl);
    });
  }
}

// 네이버 서치어드바이저 API 조회
async function queryNaverRank(keys, resultEl) {
  // Search Advisor API: 사이트 목록 조회
  try {
    const res = await fetch("https://openapi.naver.com/v1/search/webkr.json?query=테스트&display=1", {
      headers: {
        "X-Naver-Client-Id":     keys.clientId,
        "X-Naver-Client-Secret": keys.clientSecret
      }
    });
    if (res.status === 401) {
      resultEl.innerHTML = `
        <div class="seo-check-row">
          <div class="seo-light red"></div>
          <div class="seo-check-info">
            <div class="seo-check-label">인증 실패</div>
            <div class="seo-check-detail">Client ID 또는 Secret이 올바르지 않습니다</div>
          </div>
          <span class="seo-check-tag red">오류</span>
        </div>`;
      return;
    }
    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    // 키가 유효함 → Search Advisor 데이터 안내
    resultEl.innerHTML = `
      <div class="seo-check-row">
        <div class="seo-light green"></div>
        <div class="seo-check-info">
          <div class="seo-check-label">API 키 인증 성공</div>
          <div class="seo-check-detail">
            네이버 서치어드바이저의 상세 순위 데이터는
            Search Advisor API 권한이 별도 필요합니다.
            <a href="https://searchadvisor.naver.com" target="_blank"
               style="color:var(--accent)">서치어드바이저 바로가기 →</a>
          </div>
        </div>
        <span class="seo-check-tag green">연결됨</span>
      </div>`;
  } catch(e) {
    resultEl.innerHTML = `
      <div class="seo-check-row">
        <div class="seo-light red"></div>
        <div class="seo-check-info">
          <div class="seo-check-label">연결 실패</div>
          <div class="seo-check-detail">${e.message}</div>
        </div>
        <span class="seo-check-tag red">오류</span>
      </div>`;
  }
}

// 모달 닫기
document.getElementById("seoModalClose").addEventListener("click", () => {
  document.getElementById("seoModal").classList.remove("open");
});
document.getElementById("seoModal").addEventListener("click", e => {
  if (e.target === e.currentTarget) e.currentTarget.classList.remove("open");
});