<?php
$d=document($slug);$x=new DOMXPath($d);$overrides=record('page-'.$slug);$company=record('company');
if(!empty($overrides['title']))setNodeText($d->getElementsByTagName('title')->item(0),$overrides['title']);
if(!empty($overrides['description']))foreach($x->query('//meta[@name="description"]') as $meta)$meta->setAttribute('content',$overrides['description']);
foreach(textNodes($d) as $i=>$node) if(isset($overrides['text'][$i])) $node->nodeValue=$overrides['text'][$i];
foreach($x->query('//main//img[not(ancestor::*[contains(@class,"material-card")]) and not(ancestor::*[@id="catalogue"])]') as $i=>$img) if(!empty($overrides['images'][$i])) {$img->setAttribute('src',imageUrl($overrides['images'][$i]));$img->setAttribute('alt',$overrides['alts'][$i]??'');}
function replaceInner(DOMDocument $d,DOMNode $node,string $html): void {
 while($node->firstChild)$node->removeChild($node->firstChild);
 $part=new DOMDocument();$part->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
 $root=$part->getElementsByTagName('div')->item(0);foreach(iterator_to_array($root->childNodes) as $child)$node->appendChild($d->importNode($child,true));
}
function setNodeText(DOMNode $node,string $text): void {
 while($node->firstChild)$node->removeChild($node->firstChild);
 $node->appendChild($node->ownerDocument->createTextNode($text));
}
// Shared company fields stay consistent on every page, including mail and phone links.
$replacements=['Venkatramana Blue Metals'=>$company['name'],'venkatramana.vbm@gmail.com'=>$company['email'],'33AAVFV7259F1ZK'=>$company['gst'],'+91 98422 72439'=>$company['phone'],'B. Nandakumar & E. Kamalaveni'=>$company['partners'],'30+ YEARS OF INDUSTRY EXPERIENCE'=>$company['experience'].' YEARS OF INDUSTRY EXPERIENCE'];
foreach($x->query('//body//text()[not(ancestor::script) and not(ancestor::style)]') as $n)$n->nodeValue=strtr($n->nodeValue,$replacements);
foreach($x->query('//a[starts-with(@href,"mailto:")]') as $n)$n->setAttribute('href','mailto:'.$company['email']);
foreach($x->query('//a[starts-with(@href,"tel:")]') as $n)$n->setAttribute('href','tel:'.preg_replace('/[^+0-9]/','',$company['phone']));
foreach($x->query('//img[contains(@class,"company-logo")]') as $n)if($company['logo'])$n->setAttribute('src',imageUrl($company['logo']));
foreach($x->query('//address[contains(@class,"business-address")]') as $n)setNodeText($n,$company['address']);
foreach($x->query('//section[@id="company-details"]//dt') as $n)if(trim($n->textContent)==='Established')$n->nextSibling->nodeValue=$company['established'];
foreach($x->query('//section[@id="locations"]//article') as $i=>$n){
 if($i===1)replaceInner($d,$n,'<p class="eyebrow dark">BRANCH OFFICE</p><h3>Branch address</h3><p>'.nl2br(e($company['branch'])).'</p>');
 if($i===2)replaceInner($d,$n,'<p class="eyebrow dark">QUARRY &amp; CRUSHER SITE</p><h3>Our plant</h3><p>'.nl2br(e($company['site'])).'</p><p>Please call before visiting to confirm access arrangements.</p>');
}
foreach($x->query('//iframe[contains(@src,"youtube")]') as $n)$n->setAttribute('src','https://www.youtube-nocookie.com/embed/'.$company['video']);
foreach($x->query('//body//text()[not(ancestor::script)]') as $n)$n->nodeValue=str_replace(['350 TPH','350 tonnes per hour'],[$company['capacity'].' TPH',$company['capacity'].' tonnes per hour'],$n->nodeValue);
$products=array_values(array_filter(records('products'),fn($p)=>$p['published']==='1'));usort($products,fn($a,$b)=>(int)$a['order']<=>(int)$b['order']);
if($slug==='index'){
 $html='';foreach(array_slice($products,0,6) as $p){$sand=stripos($p['title'],'sand')!==false;$src=$p['image']?imageUrl($p['image']):'/assets/'.($sand?'sand.jpg':'crushed-stone.jpg');
  $html.='<article class="material-card reveal" data-category="'.($sand?'sand':'aggregate').'"><div class="rock-art"><img class="material-photo" src="'.e($src).'" alt="'.e($p['image']?$p['title']:'Representative material photograph').'" loading="lazy"></div><div class="card-copy"><h3>'.e($p['title']).'</h3><p>'.e($p['description']).'</p><button class="material-select" data-material="'.e($p['title']).'">Enquire about '.e($p['title']).' <span>+</span></button></div></article>';
 }
 foreach($x->query('//div[@class="cards"]') as $n)replaceInner($d,$n,$html?:'<p>Contact us for material availability.</p>');
}
if($slug==='products'){
 $html='<div class="section-heading"><div><p class="eyebrow dark">OUR MATERIALS</p><h2>Materials for your next project.</h2></div></div><div class="portal-grid">';
 foreach($products as $p){$html.='<article class="portal-card">';$src=$p['image']?imageUrl($p['image']):'/assets/'.(stripos($p['title'],'sand')!==false?'sand.jpg':'crushed-stone.jpg');$html.='<img loading="lazy" src="'.e($src).'" alt="'.e($p['image']?$p['title']:'Representative material photograph').'">';$html.='<h3>'.e($p['title']).'</h3><p>'.nl2br(e($p['description'])).'</p><a class="underlined" href="/contact.html?material='.rawurlencode($p['title']).'#enquiry">Enquire about this material</a></article>';}
 $html.='</div><p>Stock material photographs are representative. Contact our team to confirm grades and specifications.';
 $html.='</p>';if(!$products)$html.='<p>Contact us for current material availability.</p>';
 replaceInner($d,$x->query('//*[@id="catalogue"]')->item(0),$html);
 // The old comparison table described a different, fixed catalogue.
 foreach($x->query('//section[contains(@class,"product-guide")]') as $n)$n->parentNode->removeChild($n);
}
if($slug==='contact'){
 $html='<div class="section-heading"><h2>Licences &amp; environmental clearance.</h2></div><div class="certificate-grid">';
 $certs=records('certificates');usort($certs,fn($a,$b)=>(int)$a['order']<=>(int)$b['order']);
 foreach($certs as $c)if($c['published']==='1'){$html.='<article class="certificate-card"><h3>'.e($c['title']).'</h3><p>'.nl2br(e($c['description'])).'</p>';if($c['media'])$html.='<a class="underlined" href="/media/'.e($c['media']).'">Download certificate</a>';else $html.='<span class="certificate-status">Document not yet uploaded</span>';$html.='</article>';}
 replaceInner($d,$x->query('//*[@id="certificates"]')->item(0),$html.'</div>');
}
foreach($x->query('//select[@id="material"]') as $n){$options='';foreach($products as $p)$options.='<option>'.e($p['title']).'</option>';$options.='<option>Help me choose</option>';replaceInner($d,$n,$options);}
foreach($x->query('//form[@id="enquiry-form"]') as $form){
 $form->setAttribute('action','/enquiry');$form->setAttribute('method','post');
 $input=$d->createElement('input');$input->setAttribute('type','hidden');$input->setAttribute('name','csrf');$input->setAttribute('value',csrf());$form->appendChild($input);
 $trap=$d->createElement('input');$trap->setAttribute('name','website');$trap->setAttribute('type','hidden');$trap->setAttribute('value','');$form->appendChild($trap);
 foreach($x->query('.//button[@type="submit"]',$form) as $n)$n->nodeValue='Send enquiry';
}
foreach($x->query('//*[contains(concat(" ",normalize-space(@class)," ")," form-intro ") or contains(concat(" ",normalize-space(@class)," ")," form-note ")]') as $n)$n->nodeValue='Send your requirements directly to our team. We use your contact details only to respond to your enquiry.';
foreach($x->query('//text()[contains(.,"Download your enquiry") or contains(.,"Download the completed") or contains(.,"does not send") or contains(.,"stay in this browser")]') as $n)$n->nodeValue='Submit your enquiry to our team for review.';
foreach($x->query('//text()[contains(.,"No. It creates a text file")]') as $n)$n->nodeValue='Yes. Your enquiry is securely saved for our team to review. You can also email '.$company['email'].' or call '.$company['phone'].'.';
foreach($x->query('//script[@src="app.js"]') as $script)$script->setAttribute('src','/portal.js');
$style=$d->createElement('style','.portal-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:24px}.portal-card{background:#fff;padding:24px;border:1px solid #d8ddd8;border-radius:12px}.portal-card img{width:100%;height:220px;object-fit:cover;border-radius:8px}.portal-card h3{margin:20px 0 12px}.portal-card p{margin-bottom:20px}.business-address{white-space:pre-line}');$d->getElementsByTagName('head')->item(0)->appendChild($style);
header('Content-Type: text/html; charset=utf-8');echo $d->saveHTML();
