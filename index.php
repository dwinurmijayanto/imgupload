<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ImgTKP — Free Image Hosting</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root {
  --bg:#080a0f; --sf:#0e1117; --s2:#131720;
  --bd:#1e2535; --bd2:#263045;
  --ac:#e8ff47; --a2:#47c8ff;
  --tx:#eef1f8; --mu:#5a6177; --mu2:#3a4258;
  --ok:#47ffb2; --er:#ff5757; --wa:#ffb347;
  --r:12px; --rs:8px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  background:var(--bg);color:var(--tx);
  font-family:'DM Mono',monospace;
  min-height:100vh;display:flex;flex-direction:column;
  align-items:center;padding:2.25rem 1rem 5rem;
  position:relative;overflow-x:hidden;
}
.g{position:fixed;inset:0;pointer-events:none;z-index:0;
  background-image:linear-gradient(rgba(71,200,255,.02) 1px,transparent 1px),linear-gradient(90deg,rgba(71,200,255,.02) 1px,transparent 1px);
  background-size:52px 52px;}
.o1,.o2{position:fixed;border-radius:50%;pointer-events:none;z-index:0;}
.o1{width:700px;height:700px;background:radial-gradient(circle,rgba(71,200,255,.06) 0%,transparent 70%);top:-250px;right:-200px;}
.o2{width:500px;height:500px;background:radial-gradient(circle,rgba(232,255,71,.04) 0%,transparent 70%);bottom:-150px;left:-100px;}
.w{position:relative;z-index:1;width:100%;max-width:660px;}

.hd{text-align:center;margin-bottom:2rem;padding-top:.25rem;}
.logo{display:inline-flex;align-items:center;gap:.65rem;margin-bottom:.7rem;}
.lb{width:46px;height:46px;background:var(--a2);border-radius:11px;display:flex;align-items:center;justify-content:center;position:relative;}
.lb::after{content:'';position:absolute;inset:-3px;border-radius:14px;border:1px solid rgba(71,200,255,.3);}
.ln{font-family:'Syne',sans-serif;font-weight:800;font-size:1.65rem;letter-spacing:-.03em;}
.ln span{color:var(--a2);}
.tg{color:var(--mu);font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;}

.flow{display:flex;align-items:center;justify-content:center;gap:.45rem;margin-bottom:1.85rem;flex-wrap:wrap;}
.fn{display:flex;align-items:center;gap:.38rem;background:var(--sf);border:1px solid var(--bd);border-radius:99px;padding:.32rem .75rem;font-size:.68rem;color:var(--mu);letter-spacing:.04em;}
.fn.hl{border-color:rgba(71,200,255,.3);color:var(--a2);background:rgba(71,200,255,.05);}
.fa{color:var(--mu2);font-size:.75rem;}

.card{background:var(--sf);border:1px solid var(--bd);border-radius:var(--r);overflow:hidden;position:relative;}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--a2) 50%,transparent);opacity:.28;}
.cb{padding:2rem;}

.tabs{display:flex;gap:.28rem;background:var(--bg);border:1px solid var(--bd);border-radius:var(--rs);padding:.27rem;margin-bottom:1.65rem;}
.tab{flex:1;padding:.52rem .5rem;border:none;border-radius:6px;cursor:pointer;font-family:'DM Mono',monospace;font-size:.68rem;font-weight:500;letter-spacing:.05em;text-transform:uppercase;color:var(--mu);background:transparent;display:flex;align-items:center;justify-content:center;gap:.38rem;transition:background .2s,color .2s;}
.tab svg{width:13px;height:13px;flex-shrink:0;}
.tab.on{background:var(--bd);color:var(--tx);}

.fg{margin-bottom:1.15rem;}
label{display:block;font-size:.69rem;letter-spacing:.09em;text-transform:uppercase;color:var(--mu);margin-bottom:.48rem;}
textarea{width:100%;background:var(--bg);border:1px solid var(--bd);border-radius:var(--rs);padding:.75rem .95rem;color:var(--tx);font-family:'DM Mono',monospace;font-size:.86rem;outline:none;transition:border-color .2s,box-shadow .2s;resize:vertical;min-height:130px;line-height:1.7;}
textarea:focus{border-color:var(--a2);box-shadow:0 0 0 3px rgba(71,200,255,.08);}
textarea::placeholder{color:var(--mu2);}

.bfooter{display:flex;align-items:center;justify-content:space-between;margin-top:.38rem;margin-bottom:1.15rem;}
.bcount{font-size:.68rem;color:var(--mu);}
.bcount span{color:var(--a2);}
.bcap{font-size:.64rem;color:var(--mu2);letter-spacing:.04em;}

.btn{width:100%;padding:.88rem;background:var(--a2);border:none;border-radius:var(--rs);color:#080a0f;font-family:'Syne',sans-serif;font-size:.93rem;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.52rem;transition:transform .15s,box-shadow .15s,opacity .2s;position:relative;overflow:hidden;}
.btn::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.15) 0%,transparent 50%);opacity:0;transition:opacity .2s;}
.btn:hover:not(:disabled)::before{opacity:1;}
.btn:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 10px 28px rgba(71,200,255,.28);}
.btn:disabled{opacity:.5;cursor:not-allowed;}
.sp{display:none;width:15px;height:15px;border:2px solid rgba(8,10,15,.2);border-top-color:#080a0f;border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}

.al{margin-top:1rem;padding:.8rem .95rem;border-radius:var(--rs);font-size:.8rem;display:none;line-height:1.55;animation:fu .3s ease;}
.al.on{display:block;}
.al.er{background:rgba(255,87,87,.07);border:1px solid rgba(255,87,87,.22);color:var(--er);}

.blist,.flist{display:flex;flex-direction:column;gap:.32rem;margin-top:.5rem;max-height:420px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--bd) transparent;}
.blist::-webkit-scrollbar,.flist::-webkit-scrollbar{width:4px;}
.blist::-webkit-scrollbar-thumb,.flist::-webkit-scrollbar-thumb{background:var(--bd);border-radius:99px;}

.bitem{background:var(--bg);border:1px solid var(--bd);border-radius:var(--rs);padding:.6rem .82rem;font-size:.75rem;transition:border-color .3s,background .3s;animation:fu .25s ease;}
.bitem.ac{border-color:rgba(71,200,255,.3);background:rgba(71,200,255,.03);}
.bitem.ok{border-color:rgba(71,255,178,.22);background:rgba(71,255,178,.03);}
.bitem.er{border-color:rgba(255,87,87,.22);background:rgba(255,87,87,.03);}
.brow{display:flex;align-items:center;gap:.6rem;}
.bst{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.72rem;font-weight:700;transition:background .3s,color .3s;}
.bst.pd{background:var(--s2);color:var(--mu2);}
.bst.ac{background:rgba(71,200,255,.12);color:var(--a2);}
.bst.ok{background:rgba(71,255,178,.12);color:var(--ok);}
.bst.er{background:rgba(255,87,87,.12);color:var(--er);}
.bspin{width:11px;height:11px;border:1.5px solid rgba(71,200,255,.25);border-top-color:var(--a2);border-radius:50%;animation:spin .65s linear infinite;display:inline-block;}
.binf{flex:1;min-width:0;}
.burl2{color:var(--mu);font-size:.67rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:.14rem;}
.bres2{font-size:.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.bres2 a{color:var(--ok);text-decoration:none;transition:opacity .2s;}
.bres2 a:hover{opacity:.7;}
.bres2.er{color:var(--er);}
.bres2.mu{color:var(--mu);}
.bcpy{flex-shrink:0;padding:.3rem .55rem;border:1px solid rgba(71,255,178,.22);border-radius:5px;background:rgba(71,255,178,.07);color:var(--ok);font-family:'DM Mono',monospace;font-size:.64rem;cursor:pointer;transition:background .15s;display:none;}
.bitem.ok .bcpy{display:inline-flex;}
.bcpy:hover{background:rgba(71,255,178,.18);}

.bprg{display:none;margin-top:1rem;}
.bprg.on{display:block;}
.bph{display:flex;justify-content:space-between;align-items:center;margin-bottom:.42rem;}
.bpl{font-size:.73rem;color:var(--mu);}
.bpp2{font-size:.73rem;color:var(--a2);font-weight:500;}
.bpb{height:4px;background:var(--bd);border-radius:99px;overflow:hidden;}
.bpf{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--a2),var(--ac));transition:width .5s ease;width:0%;}

.bsum{display:none;margin-top:.9rem;padding:.82rem 1rem;background:rgba(71,255,178,.04);border:1px solid rgba(71,255,178,.18);border-radius:var(--rs);animation:fu .35s ease;}
.bsum.on{display:flex;align-items:center;justify-content:space-between;gap:.8rem;flex-wrap:wrap;}
.bsinfo{font-size:.78rem;color:var(--tx);}
.bsinfo b{color:var(--ok);}
.bsinfo s{color:var(--er);text-decoration:none;}
.bcpa{padding:.42rem .85rem;background:rgba(71,255,178,.1);border:1px solid rgba(71,255,178,.28);border-radius:6px;font-family:'DM Mono',monospace;font-size:.7rem;color:var(--ok);cursor:pointer;white-space:nowrap;transition:background .15s;}
.bcpa:hover{background:rgba(71,255,178,.2);}

.dropzone{border:2px dashed var(--bd2);border-radius:var(--r);padding:2rem 1.5rem;text-align:center;cursor:pointer;transition:border-color .25s,background .25s;background:var(--bg);position:relative;margin-bottom:1rem;}
.dropzone:hover,.dropzone.drag{border-color:rgba(71,200,255,.5);background:rgba(71,200,255,.04);}
.dropzone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.dz-icon{margin-bottom:.65rem;}
.dz-icon svg{width:34px;height:34px;color:var(--mu);transition:color .25s;}
.dropzone:hover .dz-icon svg,.dropzone.drag .dz-icon svg{color:var(--a2);}
.dz-title{font-family:'Syne',sans-serif;font-weight:700;font-size:.92rem;color:var(--tx);margin-bottom:.28rem;}
.dz-sub{font-size:.67rem;color:var(--mu);letter-spacing:.04em;}
.dz-sub span{color:var(--a2);}
.dz-formats{margin-top:.5rem;font-size:.61rem;color:var(--mu2);letter-spacing:.06em;text-transform:uppercase;}

.fgrid-wrap{margin-bottom:1rem;}
.fmeta{display:flex;align-items:center;justify-content:space-between;margin-bottom:.6rem;font-size:.68rem;color:var(--mu);}
.fmeta span{color:var(--a2);}
.fclr{padding:.28rem .6rem;background:transparent;border:1px solid var(--bd2);border-radius:5px;color:var(--mu);font-family:'DM Mono',monospace;font-size:.64rem;cursor:pointer;transition:color .15s,border-color .15s;}
.fclr:hover{color:var(--er);border-color:rgba(255,87,87,.3);}
.fgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(82px,1fr));gap:.45rem;}
.fcard{background:var(--bg);border:1px solid var(--bd);border-radius:var(--rs);overflow:hidden;position:relative;aspect-ratio:1;animation:fu .22s ease;}
.fcard img{width:100%;height:100%;object-fit:cover;display:block;}
.fcard-name{position:absolute;bottom:0;left:0;right:0;background:rgba(8,10,15,.78);padding:.22rem .32rem;font-size:.55rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--mu);}
.fcard-rm{position:absolute;top:.26rem;right:.26rem;width:17px;height:17px;background:rgba(8,10,15,.75);border:1px solid rgba(255,87,87,.4);border-radius:50%;color:var(--er);font-size:.65rem;cursor:pointer;line-height:1;display:flex;align-items:center;justify-content:center;transition:background .15s;}
.fcard-rm:hover{background:var(--er);color:#fff;border-color:var(--er);}
.fcard-badge{position:absolute;top:.26rem;left:.26rem;width:17px;height:17px;border-radius:50%;display:none;align-items:center;justify-content:center;font-size:.6rem;font-weight:700;}
.fcard-badge.ok{display:flex;background:rgba(71,255,178,.9);color:#080a0f;}
.fcard-badge.er{display:flex;background:rgba(255,87,87,.9);color:#fff;}
.fcard-overlay{position:absolute;inset:0;background:rgba(8,10,15,.55);display:none;align-items:center;justify-content:center;}
.fcard-overlay.show{display:flex;}
.fcard-overlay .bspin{width:16px;height:16px;border-width:2px;}

.ft{margin-top:1.6rem;text-align:center;color:var(--mu);font-size:.7rem;}
@keyframes fu{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:480px){.cb{padding:1.35rem;}.tab{font-size:.6rem;gap:.25rem;}}
</style>
</head>
<body>
<div class="g"></div><div class="o1"></div><div class="o2"></div>
<div class="w">

  <div class="hd">
    <div class="logo">
      <div class="lb">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
          <rect x="3" y="3" width="18" height="18" rx="3" fill="#080a0f"/>
          <path d="M3 9l4-4 4 4 4-4 4 4" stroke="#47c8ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="8" cy="14" r="2.5" fill="#47c8ff"/>
          <path d="M14 18l3-4 3 4" fill="#47c8ff" opacity=".7"/>
        </svg>
      </div>
      <span class="ln">Img<span>TKP</span></span>
    </div>
    <p class="tg">Free Image Hosting · Upload via URL atau File · Direct Link</p>
  </div>

  <div class="flow">
    <div class="fn hl">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
      Paste URL / Pilih File
    </div>
    <span class="fa">→</span>
    <div class="fn">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="7 16 12 21 17 16"/><path d="M12 21V3"/></svg>
      Upload & Simpan
    </div>
    <span class="fa">→</span>
    <div class="fn hl">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      Dapat Direct Link
    </div>
  </div>

  <div class="card">
    <div class="cb">

      <div class="tabs">
        <button class="tab on" id="turl" onclick="sw('url')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
            <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
          </svg>
          Via URL
        </button>
        <button class="tab" id="tfile" onclick="sw('file')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="3"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
          </svg>
          Via File
        </button>
      </div>

      <!-- VIA URL -->
      <div id="purl">
        <div class="fg" style="margin-bottom:.45rem">
          <label>URL Gambar <span style="color:var(--mu2)">(satu per baris · maks 20)</span></label>
          <textarea id="bta"
            placeholder="https://example.com/image1.jpg&#10;https://cdn.example.com/photo2.png&#10;..."
            oninput="parseBulk()" spellcheck="false"></textarea>
        </div>
        <div class="bfooter">
          <div class="bcount" id="bcount">Belum ada URL</div>
          <div class="bcap">Diproses satu per satu</div>
        </div>
        <button class="btn" id="bbulk" onclick="doBulk()" disabled>
          <div class="sp" id="sbulk"></div>
          <svg id="ibulk" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
          </svg>
          <span id="lbulk">Masukkan URL dulu</span>
        </button>
        <div class="bprg" id="bprg">
          <div class="bph"><span class="bpl" id="bpl">Memproses...</span><span class="bpp2" id="bpct">0 / 0</span></div>
          <div class="bpb"><div class="bpf" id="bpfill"></div></div>
        </div>
        <div class="blist" id="blist"></div>
        <div class="bsum" id="bsum"></div>
      </div>

      <!-- VIA FILE -->
      <div id="pfile" style="display:none">
        <div class="dropzone" id="dropzone"
          ondragover="dzOver(event)" ondragleave="dzLeave()" ondrop="dzDrop(event)">
          <input type="file" id="finput" accept="image/*" multiple onchange="onFilePick()">
          <div class="dz-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
              <polyline points="17 8 12 3 7 8"/>
              <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
          </div>
          <div class="dz-title">Drag & drop atau klik untuk pilih</div>
          <div class="dz-sub">Pilih satu atau banyak gambar · maks <span>20 file</span></div>
          <div class="dz-formats">JPG · PNG · GIF · WEBP · AVIF · BMP</div>
        </div>

        <div class="fgrid-wrap" id="fgrid-wrap" style="display:none">
          <div class="fmeta">
            <div><span id="fcount">0</span> file dipilih</div>
            <button class="fclr" onclick="clearFiles()">Hapus semua</button>
          </div>
          <div class="fgrid" id="fgrid"></div>
        </div>

        <button class="btn" id="bfile" onclick="doFile()" disabled>
          <div class="sp" id="sfile"></div>
          <svg id="ifile" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="7 16 12 21 17 16"/><path d="M12 21V3"/>
          </svg>
          <span id="lfile">Pilih file dulu</span>
        </button>

        <div class="bprg" id="fprg">
          <div class="bph"><span class="bpl" id="fpl">Memproses...</span><span class="bpp2" id="fpct">0 / 0</span></div>
          <div class="bpb"><div class="bpf" id="fpfill"></div></div>
        </div>
        <div class="al er" id="fale"></div>
        <div class="flist" id="flist"></div>
        <div class="bsum" id="fsum"></div>
      </div>

    </div>
  </div>

  <div class="ft"><script>document.write(window.location.hostname)</script> — &copy; 2026 BeritaTKP</div>
</div>

<script>
'use strict';

(function() {
  var _k = 0x5A;
  function _xd(s) {
    return atob(s).split('').map(function(c) {
      return String.fromCharCode(c.charCodeAt(0) ^ _k);
    }).join('');
  }
  var _e1 = 'Lyo2NTs+YgU7KjN0KjIq';
  var _e2 = 'Lyo2NTs+BSooNSIjdCoyKg==';
  var _b  = window.location.origin + '/';
  window._ra = function() { return _b + _xd(_e1); };
  window._rp = function() { return _b + _xd(_e2); };
})();
/* ════════════════════════════════════════════════════════════════ */

let bulkRunning = false, bulkLinks = [];
let fileRunning = false, fileLinks = [];
let pickedFiles = [];

// Tab switch
function sw(t) {
  ['url','file'].forEach(function(id) {
    document.getElementById('p'+id).style.display = t===id ? '' : 'none';
    document.getElementById('t'+id).classList.toggle('on', t===id);
  });
}

// ════ VIA URL (bulk) ════════════════════════════════════════════
function parseBulk() {
  var lines = document.getElementById('bta').value.split('\n').map(function(l){return l.trim();}).filter(Boolean);
  var valid = lines.filter(function(l){return l.length>10 && l.startsWith('http');}).slice(0,20);
  var inv   = lines.length - valid.length - Math.max(0, lines.length-20);
  var cnt   = document.getElementById('bcount');
  if (valid.length===0) {
    cnt.innerHTML = 'Belum ada URL valid';
  } else {
    cnt.innerHTML = '<span>'+valid.length+'</span> URL valid'
      + (lines.length>20 ? ' <em style="color:var(--wa)">(dipotong maks 20)</em>' : '')
      + (inv>0 ? ' · '+inv+' diabaikan' : '');
  }
  document.getElementById('bbulk').disabled = valid.length===0 || bulkRunning;
  document.getElementById('lbulk').textContent = valid.length===0 ? 'Masukkan URL dulu' : valid.length===1 ? 'Upload Gambar' : 'Upload '+valid.length+' Gambar';
}

async function doBulk() {
  if (bulkRunning) return;
  var urls = document.getElementById('bta').value.split('\n').map(function(l){return l.trim();}).filter(function(l){return l.length>10 && l.startsWith('http');}).slice(0,20);
  if (!urls.length) return;

  bulkRunning = true; bulkLinks = [];
  var list = document.getElementById('blist');
  list.innerHTML = '';
  document.getElementById('bsum').classList.remove('on');
  document.getElementById('bprg').classList.add('on');
  urls.forEach(function(url,i) { list.innerHTML += mkItem(i, url, 'b'); });
  ldBtn('bbulk','sbulk','ibulk','lbulk', true, 'Memproses...');

  var done=0, ok=0, fail=0;
  prog('bpfill','bpct','bpl', 0, urls.length, 'Memulai...');

  for (var i=0; i<urls.length; i++) {
    stItem(i,'ac','b'); setTxt('br'+i,'Mengupload...','mu');
    prog('bpfill','bpct','bpl', done, urls.length, 'Memproses '+(i+1)+' / '+urls.length+'...');
    try {
      var res  = await fetch(_ra()+'?url='+encodeURIComponent(urls[i]));
      var data = await res.json();
      done++;
      if (data.success && data.direct_link) {
        ok++; bulkLinks.push(data.direct_link);
        stItem(i,'ok','b');
        document.getElementById('br'+i).innerHTML = '<a href="'+esc(data.direct_link)+'" target="_blank">'+esc(data.direct_link)+'</a>';
        document.getElementById('br'+i).className = 'bres2';
        document.getElementById('bcp'+i).dataset.url = data.direct_link;
      } else { fail++; stItem(i,'er','b'); setTxt('br'+i, data.error||'Upload gagal','er'); }
    } catch(e) { done++; fail++; stItem(i,'er','b'); setTxt('br'+i,'Terjadi kesalahan','er'); }
    prog('bpfill','bpct','bpl', done, urls.length, done===urls.length ? 'Selesai!' : null);
    document.getElementById('bi'+i).scrollIntoView({behavior:'smooth',block:'nearest'});
  }

  bulkRunning = false;
  ldBtn('bbulk','sbulk','ibulk','lbulk', false, ''); parseBulk();
  mkSum('bsum', ok, fail, urls.length, bulkLinks, 'cpAllBulk');
}

// ════ VIA FILE ══════════════════════════════════════════════════
function dzOver(e)  { e.preventDefault(); document.getElementById('dropzone').classList.add('drag'); }
function dzLeave()  { document.getElementById('dropzone').classList.remove('drag'); }
function dzDrop(e)  { e.preventDefault(); document.getElementById('dropzone').classList.remove('drag'); addFiles(Array.from(e.dataTransfer.files)); }
function onFilePick() { var inp=document.getElementById('finput'); addFiles(Array.from(inp.files)); inp.value=''; }

var ALLOWED = ['image/jpeg','image/png','image/gif','image/webp','image/avif','image/bmp'];

function addFiles(newFiles) {
  pickedFiles = pickedFiles.concat(newFiles.filter(function(f){return ALLOWED.includes(f.type);})).slice(0,20);
  renderPreviews();
}

function renderPreviews() {
  var grid = document.getElementById('fgrid');
  var wrap = document.getElementById('fgrid-wrap');
  grid.innerHTML = '';
  if (pickedFiles.length===0) {
    wrap.style.display='none';
    document.getElementById('bfile').disabled=true;
    document.getElementById('lfile').textContent='Pilih file dulu';
    return;
  }
  wrap.style.display='block';
  document.getElementById('fcount').textContent = pickedFiles.length;
  document.getElementById('bfile').disabled = fileRunning;
  document.getElementById('lfile').textContent = pickedFiles.length===1 ? 'Upload Gambar' : 'Upload '+pickedFiles.length+' Gambar';
  pickedFiles.forEach(function(f,i) {
    var objUrl = URL.createObjectURL(f);
    var card = document.createElement('div');
    card.className='fcard'; card.id='fc'+i;
    card.innerHTML = '<img src="'+objUrl+'" onload="URL.revokeObjectURL(this.src)">'
      + '<div class="fcard-overlay" id="fov'+i+'"><div class="bspin"></div></div>'
      + '<div class="fcard-badge" id="fbg'+i+'"></div>'
      + '<button class="fcard-rm" id="frm'+i+'" onclick="removeFile('+i+')">&#x2715;</button>'
      + '<div class="fcard-name">'+esc(f.name)+'</div>';
    grid.appendChild(card);
  });
}

function removeFile(i) {
  if (fileRunning) return;
  pickedFiles.splice(i,1); renderPreviews();
  document.getElementById('flist').innerHTML='';
  ['fsum','fprg','fale'].forEach(function(id){document.getElementById(id).classList.remove('on');});
}

function clearFiles() {
  if (fileRunning) return;
  pickedFiles=[]; renderPreviews();
  document.getElementById('flist').innerHTML='';
  ['fsum','fprg','fale'].forEach(function(id){document.getElementById(id).classList.remove('on');});
}

async function doFile() {
  if (fileRunning || pickedFiles.length===0) return;
  fileRunning=true; fileLinks=[];

  var flist=document.getElementById('flist');
  flist.innerHTML='';
  ['fsum','fale'].forEach(function(id){document.getElementById(id).classList.remove('on');});
  document.getElementById('fprg').classList.add('on');
  pickedFiles.forEach(function(f,i) { flist.innerHTML += mkItem(i, f.name, 'f'); });
  ldBtn('bfile','sfile','ifile','lfile', true, 'Memproses...');
  pickedFiles.forEach(function(_,i){ var r=document.getElementById('frm'+i); if(r) r.style.display='none'; });

  var done=0, ok=0, fail=0;
  prog('fpfill','fpct','fpl', 0, pickedFiles.length, 'Memulai...');

  for (var i=0; i<pickedFiles.length; i++) {
    var file = pickedFiles[i];
    var ov   = document.getElementById('fov'+i);
    if (ov) ov.classList.add('show');
    stItem(i,'ac','f'); setTxt('fr'+i,'Mengupload...','mu');
    prog('fpfill','fpct','fpl', done, pickedFiles.length, 'Memproses '+(i+1)+' / '+pickedFiles.length+'...');

    try {
      var fd = new FormData();
      fd.append('images[]', file, file.name);

      var res  = await fetch(_rp(), { method:'POST', body: fd });
      var data = await res.json();

      done++;
      if (data.success && data.direct_link) {
        ok++; fileLinks.push(data.direct_link);
        stItem(i,'ok','f');
        document.getElementById('fr'+i).innerHTML = '<a href="'+esc(data.direct_link)+'" target="_blank">'+esc(data.direct_link)+'</a>';
        document.getElementById('fr'+i).className='bres2';
        document.getElementById('fcp'+i).dataset.url = data.direct_link;
        var bg=document.getElementById('fbg'+i); if(bg){bg.className='fcard-badge ok';bg.textContent='✓';}
      } else {
        fail++;
        stItem(i,'er','f');
        setTxt('fr'+i, data.error||'Upload gagal','er');
        var bg=document.getElementById('fbg'+i); if(bg){bg.className='fcard-badge er';bg.textContent='✗';}
      }
    } catch(err) {
      done++; fail++;
      stItem(i,'er','f');
      setTxt('fr'+i, 'Network error: '+err.message,'er');
      var bg=document.getElementById('fbg'+i); if(bg){bg.className='fcard-badge er';bg.textContent='✗';}
    }

    if(ov) ov.classList.remove('show');
    prog('fpfill','fpct','fpl', done, pickedFiles.length, done===pickedFiles.length ? 'Selesai!' : null);
    document.getElementById('fi'+i).scrollIntoView({behavior:'smooth',block:'nearest'});
  }

  fileRunning=false;
  ldBtn('bfile','sfile','ifile','lfile', false, '');
  pickedFiles.forEach(function(_,i){ var r=document.getElementById('frm'+i); if(r) r.style.display=''; });
  renderPreviews();
  mkSum('fsum', ok, fail, pickedFiles.length, fileLinks, 'cpAllFile');
}

// ════ Copy helpers ═══════════════════════════════════════════════
function cpItem(i,pfx) {
  var url=document.getElementById(pfx+'cp'+i).dataset.url; if(!url) return;
  navigator.clipboard.writeText(url).then(function(){
    var b=document.getElementById(pfx+'cp'+i); var o=b.textContent; b.textContent='✓';
    setTimeout(function(){b.textContent=o;},1800);
  });
}
function cpAllBulk() { if(bulkLinks.length) navigator.clipboard.writeText(bulkLinks.join('\n')).then(function(){flashTip(bulkLinks.length+' link disalin!');});}
function cpAllFile() { if(fileLinks.length) navigator.clipboard.writeText(fileLinks.join('\n')).then(function(){flashTip(fileLinks.length+' link disalin!');});}

// ════ UI helpers ══════════════════════════════════════════════════
function mkItem(i, label, pfx) {
  return '<div class="bitem" id="'+pfx+'i'+i+'">'
    +'<div class="brow">'
    +'<div class="bst pd" id="'+pfx+'st'+i+'">·</div>'
    +'<div class="binf">'
    +'<div class="burl2" title="'+esc(label)+'">'+esc(label)+'</div>'
    +'<div class="bres2 mu" id="'+pfx+'r'+i+'">Menunggu...</div>'
    +'</div>'
    +'<button class="bcpy" id="'+pfx+'cp'+i+'" onclick="cpItem('+i+',\''+pfx+'\')">Copy</button>'
    +'</div></div>';
}

function stItem(i,s,pfx) {
  document.getElementById(pfx+'i'+i).className='bitem '+s;
  var st=document.getElementById(pfx+'st'+i);
  st.className='bst '+s;
  if(s==='ac') st.innerHTML='<div class="bspin"></div>';
  else if(s==='ok') st.textContent='✓';
  else if(s==='er') st.textContent='✗';
  else st.textContent='·';
}

function setTxt(id,text,cls) {
  var el=document.getElementById(id);
  el.textContent=text; el.className='bres2'+(cls?' '+cls:'');
}

function prog(fillId,cntId,lblId,done,total,label) {
  var pct=total>0?Math.round(done/total*100):0;
  document.getElementById(fillId).style.width=pct+'%';
  document.getElementById(cntId).textContent=done+' / '+total;
  if(label) document.getElementById(lblId).textContent=label;
}

function ldBtn(btnId,spId,iconId,lblId,loading,txt) {
  document.getElementById(btnId).disabled=loading;
  document.getElementById(spId).style.display=loading?'block':'none';
  document.getElementById(iconId).style.display=loading?'none':'block';
  if(loading) document.getElementById(lblId).textContent=txt;
}

function mkSum(sumId,ok,fail,total,links,cpFn) {
  var sum=document.getElementById(sumId);
  sum.innerHTML='<div class="bsinfo"><b>'+ok+' berhasil</b> · <s>'+fail+' gagal</s> · total '+total+'</div>'
    +(links.length>0 ? '<button class="bcpa" onclick="'+cpFn+'()">Copy Semua ('+links.length+')</button>' : '');
  sum.classList.add('on');
}

function flashTip(msg) {
  var d=document.createElement('div');
  d.textContent=msg;
  Object.assign(d.style,{position:'fixed',bottom:'1.5rem',left:'50%',transform:'translateX(-50%)',
    background:'var(--ok)',color:'#080a0f',padding:'.5rem 1.2rem',borderRadius:'99px',
    fontFamily:"'Syne',sans-serif",fontWeight:'800',fontSize:'.85rem',
    zIndex:'9999',animation:'fu .3s ease',boxShadow:'0 8px 24px rgba(71,255,178,.3)'});
  document.body.appendChild(d);
  setTimeout(function(){d.remove();},2200);
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v8c78df7c7c0f484497ecbca7046644da1771523124516" integrity="sha512-8DS7rgIrAmghBFwoOTujcf6D9rXvH8xm8JQ1Ja01h9QX8EzXldiszufYa4IFfKdLUKTTrnSFXLDkUEOTrZQ8Qg==" data-cf-beacon='{"version":"2024.11.0","token":"6d4fe2a941cb4b2ca0068cb7039b6db2","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
</body>
</html>