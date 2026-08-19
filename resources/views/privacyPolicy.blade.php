@extends('template')

@section('title', 'Privacy Policy')

@section('content')
<div class="container page">

	<div class="row">
		<div class="col-md-12">
            <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
			<meta name="description" content="Website Privacy Policy">
			<meta name="robots" content="noindex">
            <style type="text/css">
            @page{margin:90px 50px 50px;@prince-overlay{content:flow(watermark)}@top{content:flow(header);vertical-align:bottom;padding:50px 0 20px}@bottom{content:flow(footer);margin-bottom:30px}@bottom-left{content:string(copyText, last);width:0;overflow:visible;white-space:nowrap;font-family:myriad-pro,sans-serif;font-size:9pt;color:#bcbec0}}@page :first{margin-top:50px;@top{content:flow(firstHeader)}@bottom{content:flow(firstFooter);margin-bottom:30px}}.templated .imageList{display:block;margin-left:-25px;margin-bottom:30px}.templated .imageList .imageAndData{display:inline-block;vertical-align:top;margin-left:25px;margin-top:30px}.templated .imageList .imageAndData .imageWrapper{display:flex;align-items:center;justify-content:center}.templated .imageList .imageAndData .imageWrapper img{max-width:100%;max-height:100%}.templated .imageList .imageAndData .comment{display:block;overflow-wrap:break-word}.templated .copyright{visibility:hidden;height:0;string-set:copyText content()}.templated .currentPageNum{content:counter(page)}.templated .totalPageNum{content:counter(pages)}.templated .header{flow:static(header)}.templated .firstHeader{flow:static(firstHeader)}.templated .footer{flow:static(footer)}.templated .firstFooter{flow:static(firstFooter)}.templated .printWatermark{flow:static(watermark)}.templated .pageBreak{page-break-before:always;display:block}.templated .blankLine{display:block;border-bottom:1px solid #000}.templated .keepTogether,.templated .signatureEntity{page-break-inside:avoid}.templated .outputDocument{counter-reset:page 1 pages 1;prince-page-group:start;string-set:copyText ""}.templated li ol:first-child,.templated li ol:first-child>li:first-child,.templated li ul:first-child,.templated li ul:first-child>li:first-child{margin-top:0}.templated .inlineLogoHeader{display:flex;margin-bottom:30px;align-items:center;justify-content:center}.templated .inlineLogoHeader .imageList,.templated .inlineLogoHeader .imageAndData,.templated .inlineLogoHeader .keepTogether p:last-child{margin:0}.templated .inlineLogoHeader .imageWrapper{padding-right:25px}.sm .templated .imageList{margin-bottom:15px}.sm .templated .imageList div{margin-top:15px}@media screen{.templated .header,.templated .footer,.templated .firstHeader,.templated .firstFooter,.templated .copyright,.templated .printWatermark{display:none}}@media print{.LDCopyright,.watermark{display:none}}@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 400;
  src: url(/common/fonts/Lato/Lato-Regular.eot);
  src: local('Lato Regular'), local('Lato-Regular'), url(/common/fonts/Lato/Lato-Regular.woff2) format('woff2'), url(/common/fonts/Lato/Lato-Regular.woff) format('woff'), url(/common/fonts/Lato/Lato-Regular.svg#Lato) format('svg'), url(/common/fonts/Lato/Lato-Regular.ttf) format('truetype'), url(/common/fonts/Lato/Lato-Regular.eot?#iefix) format('embedded-opentype');
  font-display: block;
}

@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 700;
  src: url(/common/fonts/Lato/Lato-Bold.eot);
  src: local('Lato Bold'), local('Lato-Bold'), url(/common/fonts/Lato/Lato-Bold.woff2) format('woff2'), url(/common/fonts/Lato/Lato-Bold.woff) format('woff'), url(/common/fonts/Lato/Lato-Bold.ttf) format('truetype'), url(/common/fonts/Lato/Lato-Bold.eot?#iefix) format('embedded-opentype');
  font-display: block;
}

@font-face {
  font-family: 'Lato';
  font-style: normal;
  font-weight: 900;
  src: url(/common/fonts/Lato/Lato-Black.eot);
  src: local('Lato Black'), local('Lato-Black'), url(/common/fonts/Lato/Lato-Black.woff2) format('woff2'), url(/common/fonts/Lato/Lato-Black.woff) format('woff'), url(/common/fonts/Lato/Lato-Black.ttf) format('truetype'), url(/common/fonts/Lato/Lato-Black.eot?#iefix) format('embedded-opentype');
  font-display: block;
}

@font-face {
  font-family: 'Lato';
  font-style: italic;
  font-weight: 400;
  src: url(/common/fonts/Lato/Lato-Italic.eot);
  src: local('Lato Italic'), local('Lato-Italic'), url(/common/fonts/Lato/Lato-Italic.woff2) format('woff2'), url(/common/fonts/Lato/Lato-Italic.woff) format('woff'), url(/common/fonts/Lato/Lato-Italic.ttf) format('truetype'), url(/common/fonts/Lato/Lato-Italic.eot?#iefix) format('embedded-opentype');
  font-display: block;
}

@font-face {
  font-family: 'Lato';
  font-style: italic;
  font-weight: 700;
  src: url(/common/fonts/Lato/Lato-Bold-Italic.eot);
  src: local('Lato Bold Italic'), local('Lato-BoldItalic'), url(/common/fonts/Lato/Lato-Bold-Italic.woff2) format('woff2'), url(/common/fonts/Lato/Lato-Bold-Italic.woff) format('woff'), url(/common/fonts/Lato/Lato-Bold-Italic.ttf) format('truetype'), url(/common/fonts/Lato/Lato-Bold-Italic.eot?#iefix) format('embedded-opentype');
  font-display: block;
}
@font-face {
  font-family: 'Open Sans';
  font-style: normal;
  font-weight: 300;
  src: url(/common/fonts/Open-Sans/opensans-light.eot);
  src: local('Open-Sans Light'), local('OpenSans-Light'), url(/common/fonts/Open-Sans/opensans-light.woff2) format('woff2'), url(/common/fonts/Open-Sans/opensans-light.woff) format('woff'), url(/common/fonts/Open-Sans/opensans-light.ttf) format('truetype'), url(/common/fonts/Open-Sans/opensans-light.eot?#iefix) format('embedded-opentype'), url(/common/fonts/Open-Sans/opensans-light.svg#opensans-light) format('svg');
  font-display: swap;
}

@font-face {
  font-family: 'Open Sans';
  font-style: normal;
  font-weight: 400;
  src: url(/common/fonts/Open-Sans/opensans-regular.eot);
  src: local('Open-Sans Regular'), local('OpenSans-Regular'), url(/common/fonts/Open-Sans/opensans-regular.woff2) format('woff2'), url(/common/fonts/Open-Sans/opensans-regular.woff) format('woff'), url(/common/fonts/Open-Sans/opensans-regular.ttf) format('truetype'), url(/common/fonts/Open-Sans/opensans-regular.eot?#iefix) format('embedded-opentype'), url(/common/fonts/Open-Sans/opensans-regular.svg#opensans-regular) format('svg');
  font-display: swap;
}

@font-face {
  font-family: 'Open Sans';
  font-style: normal;
  font-weight: 600;
  src: url(/common/fonts/Open-Sans/opensans-semibold.eot);
  src: local('Open-Sans SemiBold'), local('OpenSans-SemiBold'), url(/common/fonts/Open-Sans/opensans-semibold.woff2) format('woff2'), url(/common/fonts/Open-Sans/opensans-semibold.woff) format('woff'), url(/common/fonts/Open-Sans/opensans-semibold.ttf) format('truetype'), url(/common/fonts/Open-Sans/opensans-semibold.eot?#iefix) format('embedded-opentype'), url(/common/fonts/Open-Sans/opensans-semibold.svg#opensans-semibold) format('svg');
  font-display: swap;
}

@font-face {
  font-family: 'Open Sans';
  font-style: normal;
  font-weight: 700;
  src: url(/common/fonts/Open-Sans/opensans-bold.eot);
  src: local('Open-Sans Bold'), local('OpenSans-Bold'), url(/common/fonts/Open-Sans/opensans-bold.woff2) format('woff2'), url(/common/fonts/Open-Sans/opensans-bold.woff) format('woff'), url(/common/fonts/Open-Sans/opensans-bold.ttf) format('truetype'), url(/common/fonts/Open-Sans/opensans-bold.eot?#iefix) format('embedded-opentype'), url(/common/fonts/Open-Sans/opensans-bold.svg#opensans-bold) format('svg');
  font-display: swap;
}

@font-face {
  font-family: 'Open Sans';
  font-style: normal;
  font-weight: 800;
  src: url(/common/fonts/Open-Sans/opensans-extrabold.eot);
  src: local('Open-Sans Extra Bold'), local('OpenSans-ExtraBold'), url(/common/fonts/Open-Sans/opensans-extrabold.woff2) format('woff2'), url(/common/fonts/Open-Sans/opensans-extrabold.woff) format('woff'), url(/common/fonts/Open-Sans/opensans-extrabold.ttf) format('truetype'), url(/common/fonts/Open-Sans/opensans-extrabold.eot?#iefix) format('embedded-opentype'), url(/common/fonts/Open-Sans/opensans-extrabold.svg#opensans-extrabold) format('svg');
  font-display: swap;
}

@font-face {
  font-family: 'Open Sans';
  font-style: italic;
  font-weight: 300;
  src: url(/common/fonts/Open-Sans/opensans-lightitalic.eot);
  src: local('Open-Sans Light Italic'), local('OpenSansLight-Italic'), url(/common/fonts/Open-Sans/opensans-lightitalic.woff2) format('woff2'), url(/common/fonts/Open-Sans/opensans-lightitalic.woff) format('woff'), url(/common/fonts/Open-Sans/opensans-lightitalic.ttf) format('truetype'), url(/common/fonts/Open-Sans/opensans-lightitalic.eot?#iefix) format('embedded-opentype'), url(/common/fonts/Open-Sans/opensans-lightitalic.svg#opensans-lightitalic) format('svg');
  font-display: swap;
}

@font-face {
  font-family: 'Open Sans';
  font-style: italic;
  font-weight: 400;
  src: url(/common/fonts/Open-Sans/opensans-italic.eot);
  src: local('Open-Sans Italic'), local('OpenSans-Italic'), url(/common/fonts/Open-Sans/opensans-italic.woff2) format('woff2'), url(/common/fonts/Open-Sans/opensans-italic.woff) format('woff'), url(/common/fonts/Open-Sans/opensans-italic.ttf) format('truetype'), url(/common/fonts/Open-Sans/opensans-italic.eot?#iefix) format('embedded-opentype'), url(/common/fonts/Open-Sans/opensans-italic.svg#opensans-italic) format('svg');
  font-display: swap;
}

@font-face {
  font-family: 'Open Sans';
  font-style: italic;
  font-weight: 600;
  src: url(/common/fonts/Open-Sans/opensans-semibolditalic.eot);
  src: local('Open-Sans SemiBold Italic'), local('OpenSansSemiBold-Italic'), url(/common/fonts/Open-Sans/opensans-semibolditalic.woff2) format('woff2'), url(/common/fonts/Open-Sans/opensans-semibolditalic.woff) format('woff'), url(/common/fonts/Open-Sans/opensans-semibolditalic.ttf) format('truetype'), url(/common/fonts/Open-Sans/opensans-semibolditalic.eot?#iefix) format('embedded-opentype'), url(/common/fonts/Open-Sans/opensans-semibolditalic.svg#opensans-semibolditalic) format('svg');
  font-display: swap;
}

@font-face {
  font-family: 'Open Sans';
  font-style: italic;
  font-weight: 700;
  src: url(/common/fonts/Open-Sans/opensans-bolditalic.eot);
  src: local('Open-Sans Bold Italic'), local('OpenSansBold-Italic'), url(/common/fonts/Open-Sans/opensans-bolditalic.woff2) format('woff2'), url(/common/fonts/Open-Sans/opensans-bolditalic.woff) format('woff'), url(/common/fonts/Open-Sans/opensans-bolditalic.ttf) format('truetype'), url(/common/fonts/Open-Sans/opensans-bolditalic.eot?#iefix) format('embedded-opentype'), url(/common/fonts/Open-Sans/opensans-bolditalic.svg#opensans-bolditalic) format('svg');
  font-display: swap;
}

@font-face {
  font-family: 'Open Sans';
  font-style: italic;
  font-weight: 800;
  src: url(/common/fonts/Open-Sans/opensans-extrabolditalic.eot);
  src: local('Open-Sans ExtraBold Italic'), local('OpenSansExtraBold-Italic'), url(/common/fonts/Open-Sans/opensans-extrabolditalic.woff2) format('woff2'), url(/common/fonts/Open-Sans/opensans-extrabolditalic.woff) format('woff'), url(/common/fonts/Open-Sans/opensans-extrabolditalic.ttf) format('truetype'), url(/common/fonts/Open-Sans/opensans-extrabolditalic.eot?#iefix) format('embedded-opentype'), url(/common/fonts/Open-Sans/opensans-extrabolditalic.svg#opensans-extrabolditalic) format('svg');
  font-display: swap;
}
@page{margin:60px 35px 35px}@page :first{margin-top:35px}.template_test{font-family:"Lato";font-size:10pt}.template_test .pageBreak{page-break-before:always}.template_test .blankLine{display:block;border-bottom:1px solid #000}.template_test .keepTogether{page-break-inside:avoid}.template_test h1{font-size:22pt;text-align:center;padding:15px 0;margin:15px 0;border-bottom:2px solid #000;font-weight:900}.template_test h2{text-align:center;font-size:14pt;text-transform:uppercase;font-weight:700;overflow:hidden;margin:20px 0}.template_test h2::before,.template_test h2::after{background-color:#000;content:"";display:inline-block;height:1px;position:relative;vertical-align:middle;width:50%}.template_test h2::before{right:10px;margin-left:-50%}.template_test h2::after{left:10px;margin-right:-50%}.template_test .contactInfo{text-align:center;margin:15px 0}.template_test .contactInfo span::before{content:" | ";padding:0 5px}.template_test .contactInfo span:first-child::before{content:"";padding:0}.template_test p{margin:20px 0}.template_test .infoHeading{margin:20px 0 10px;position:relative}.template_test .infoHeading .mainTitle,.template_test .infoHeading .mainInfo{font-weight:900;font-size:12pt;display:block;max-width:50%}.template_test .infoHeading .subTitle,.template_test .infoHeading .subInfo{font-weight:700;font-size:11pt;display:block;max-width:50%}.template_test .infoHeading .mainInfo{position:absolute;top:0;right:0}.template_test .infoHeading .subInfo{position:absolute;bottom:0;right:0}.template_test .infoHeading.education .subInfo{font-weight:normal;font-style:italic;position:static;display:block;font-size:10pt;max-width:100%}.template_test .infoHeading.award .subInfo,.template_test .infoHeading.volunteer .subInfo{font-weight:normal;position:static;display:block;font-size:10pt;max-width:100%}.template_test ul{margin:0;list-style-type:none}.template_test ul li::before{content:"â€¢";position:absolute;left:20px}.template_test li{margin:10px 0;padding-left:33px;position:relative;box-sizing:border-box}.template_test .columns{font-size:0}.template_test .columns li{display:inline-block;width:50%;font-size:10pt;margin:5px 0;vertical-align:top}.template_test .workSample{margin-left:20px}.ua-mobile .template_test h2::before,.ua-mobile .template_test h2::after{height:2px}.ua-android.ua-webkit .template_test .columns{font-size:10pt}.ua-android.ua-webkit .template_test .columns li{width:49%}body, html {
	background: #FFF;
}
noscript,#navigation,#banner,#footer,#intro,#outtro,.ImportantInfoDialog,
.headerSimplePreview, .sectionMenuTop, .selLicense, .previewHead,
iframe, iframe *, .noMobile, .woahbar, .contractFootControls, .previewFormatWarning {
	display:none;
}
#upsell, #popupContainer, #popupMask, .contentBreak, .contentBreakEnd {
   display:none !important;
}
#popupContainer, #popupMask {
	visibility: hidden;
}
#wrapper,#content {
   background:none;
	position:relative;
	display:block;
	width:auto;
}
#outputPage {
	display:block;
	padding:3px;
	background:#FFF;
	color:#000;
	position:relative;
	mso-style-parent:"";
	margin:0;
	margin-bottom:0.0001pt;
	mso-pagination:widow-orphan;
	tab-stops:-.5in;
	font-size:12pt;
	font-family:"Times New Roman", serif;
	mso-fareast-font-family:"Times New Roman";
	line-height: 18pt;
}
#outputPage {
   width:100%;
   background:none;
}
#outputPage table{border-collapse:collapse}
#outputPage ol { list-style:decimal;}
#outputPage ol ol { list-style:lower-alpha;}
#outputPage ol ol ol { list-style:lower-roman;}
#outputPage ol ol ol ol { list-style:decimal; list-style-position: inherit; }
#outputPage br.pageBreak{page-break-before:always}
 
@media screen {
	#outputPage br.pageBreak{display:none}	
} 
#outputPage .outputVersion2 p,#outputPage .outputVersion2 ol,#outputPage .outputVersion2 ul,#outputPage .outputVersion2 table{margin-bottom:0}
#outputPage .outputVersion2 ol,#outputPage .outputVersion2 ul{margin-left:0;padding-left:0}
#outputPage .outputVersion2 li > div{display:table;*display:inline;*margin-left:-17px}
#outputPage .format-doc .outputVersion2 li,
#outputPage .format-rtf .outputVersion2 li,
#outputPage .format-docx .outputVersion2 li,
#outputPage .format-pdf .outputVersion2 li {margin-left:18pt}
#outputPage .outputVersion2{padding-top:43pt}
.LDCopyright {
	font-size:67%;
}
.LDCellCenter {
	text-align:center;
}
.LDCellPadLeft {
	padding-left:100px;
}
.LDCellRight {
	text-align:right;
}
.LDResRentalBox {
   width:100%;
}
.LDQuitclaimHead {
   height:3in;
}
.LDWarrantyHead {
   height:3in;
}
.LDQuitclaimHeadCA {
   height:1in;
}
.LDWarrantyHeadCA {
   height:1in;
}
.LDWarrantyHeadMO {
   height:2in;
}
.LDQuitclaimHeadMO {
   height:2in;
}
.SubHeadStyle {
	font-weight:bold;
	text-decoration:underline;
}
.SectionTitle{
   font-weight:bold;
   text-decoration:underline;
   margin:1em 0;
}
.invoiceBorder {
   border:1px solid #000;
}
.invoiceGreyBG {
   background-color:#ccc;
}
.LDBold {
   font-weight:bold;
}
.LDBackgroundOL {
	list-style-type:upper-alpha;
}
#bottomTabs, #topTabs, #introContent, #loadingIndicator, #questionpage, #documentControls, #questionpage { display:none }
#productContent, #documentContent { width:100%;border:0;margin:0 }
#outputPage li{
   margin-bottom:1em;
}
#outputPage li li{
   margin-bottom:1em;
}

#outputPage li ol {
	margin-top: 1em;
}
#outputPage li ol li {
	margin-bottom: 1em;
}
#outputPage li.lh,#outputPage li.lhl {
	margin-bottom: 0;
	list-style: none;
}
.FirstMajorListHeading { 
	font-weight:bold;
	margin-bottom:1.6em;
}
.FirstListHeading {
	font-weight: bold;
	text-decoration: underline;
	font-size:12pt;
	margin:1.4em 0 0.2em 2em; /* 2em should be set to left margin of lists */
}
.ListHeading {
	font-weight: bold;
	text-decoration: underline;
}

 div.header,
 div.firstHeader,
 div.footer,
 div.firstFooter{ display: none;}

 .blankLine {
	display: block;
	border-bottom: solid 1px #000;
}



/* End Alerts/Confirms Inline Dialogs */
/* Mobile Scrollbars */
.ua-mobile .contractbody::-webkit-scrollbar,
.ua-mobile #contractbody::-webkit-scrollbar,
.ua-mobile .groupNavigationInner > ul::-webkit-scrollbar,
.ua-mobile #contentTopInnerDisclaimer::-webkit-scrollbar,
.ua-mobile #contentTopInnerPrivacy::-webkit-scrollbar,
.ua-mobile #contentTopInnerEditorialPolicy::-webkit-scrollbar,
.ua-mobile #contentTopInnerTerms::-webkit-scrollbar,
.ua-mac_os_x .contractbody::-webkit-scrollbar,
.ua-mac_os_x #contractbody::-webkit-scrollbar,
.ua-mac_os_x .groupNavigationInner > ul::-webkit-scrollbar,
.ua-mac_os_x #contentTopInnerDisclaimer::-webkit-scrollbar,
.ua-mac_os_x #contentTopInnerPrivacy::-webkit-scrollbar,
.ua-mac_os_x #contentTopInnerEditorialPolicy::-webkit-scrollbar,
.ua-mac_os_x #contentTopInnerTerms::-webkit-scrollbar,
.ua-mobile #DIVContractList::-webkit-scrollbar,
.mac_os_x #DIVContractList::-webkit-scrollbar,
.ua-mobile #tip::-webkit-scrollbar,
.ua-mac_os_x #tip::-webkit-scrollbar,
.ua-mac_os_x ul.ui-autocomplete::-webkit-scrollbar,
.ua-mobile ul.ui-autocomplete::-webkit-scrollbar,
.ua-mobile:not(.ua-ios) .inlineFooterDialog .ui-dialog-content::-webkit-scrollbar,
.addRecipientDialog::-webkit-scrollbar
{
   -webkit-appearance: none;
   width: 7px;
}
.ua-mobile .contractbody::-webkit-scrollbar-thumb,
.ua-mobile #contractbody::-webkit-scrollbar-thumb,
.ua-mobile .groupNavigationInner > ul::-webkit-scrollbar-thumb,
.ua-mobile #contentTopInnerDisclaimer::-webkit-scrollbar-thumb,
.ua-mobile #contentTopInnerPrivacy::-webkit-scrollbar-thumb,
.ua-mobile #contentTopInnerEditorialPolicy::-webkit-scrollbar-thumb,
.ua-mobile #contentTopInnerTerms::-webkit-scrollbar-thumb,
.ua-mac_os_x .contractbody::-webkit-scrollbar-thumb,
.ua-mac_os_x #contractbody::-webkit-scrollbar-thumb,
.ua-mac_os_x .groupNavigationInner > ul::-webkit-scrollbar-thumb,
.ua-mac_os_x #contentTopInnerDisclaimer::-webkit-scrollbar-thumb,
.ua-mac_os_x #contentTopInnerPrivacy::-webkit-scrollbar-thumb,
.ua-mac_os_x #contentTopInnerEditorialPolicy::-webkit-scrollbar-thumb,
.ua-mac_os_x #contentTopInnerTerms::-webkit-scrollbar-thumb,
.ua-mac_os_x #DIVContractList::-webkit-scrollbar-thumb,
.ua-mobile #DIVContractList::-webkit-scrollbar-thumb,
.ua-mobile #tip::-webkit-scrollbar-thumb, 
.ua-mac_os_x #tip::-webkit-scrollbar-thumb,
.ua-mac_os_x ul.ui-autocomplete::-webkit-scrollbar-thumb,
.ua-mobile ul.ui-autocomplete::-webkit-scrollbar-thumb,
.ua-mobile:not(.ua-ios) .inlineFooterDialog .ui-dialog-content::-webkit-scrollbar-thumb,
.addRecipientDialog::-webkit-scrollbar-thumb
{
   border-radius: 4px;
   background-color: rgba(0,0,0,.5);
   -webkit-box-shadow: 0 0 1px rgba(255,255,255,.5);
}
/* End special links */
/* General DCS Output Styles */
body li>ol:first-child, body li>ul:first-child {margin-top:0;}
.ua-edge body li>ol:first-child, .ua-edge body li>ul:first-child, .ua-ie body li>ol:first-child, .ua-ie body li>ul:first-child  {margin-top:-24px;}
body li ol, body li ul {
   margin-top:1em;
}
body li{
   margin-bottom:1em;
}
body li.lh,body li.lhl{
   margin-bottom:0;
}
body th{font-weight:normal;}
body td>p:first-child, body th>p:first-child {margin-top:0}
body td>p:last-child, body th>p:last-child {margin-bottom:0}
body br.pageBreak{display:none}
body .blankLine {display:block; border-bottom:solid 1px black;}body ol,body ul{margin-left:0;padding-left:0}body .header,body .footer,body .firstHeader,body .firstFooter,body .printWatermark{display:none}body li{padding-left:25px;margin-left:15px}body #outputPage,body .documentContent,body .contract{padding:2em;overflow-wrap:break-word;word-wrap:break-word;word-break:break-word;overflow-x:hidden}body .outputVersion2 li.lhl{display:block}body .outputVersion2 ol ol{list-style:lower-alpha}body .outputVersion2 ol ol ol{list-style:lower-roman}body .outputVersion2 li>div{display:table;margin:0 !important;padding:0 !important}.LD body #contractbody li,.LD body .contractbody li{padding-left:25px;margin-left:30px}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active{padding-top:52px !important}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active .paymentValidator,.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active .securityCodeValidator{margin:0 !important}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active .qd.textPaymentInputWrapper.stxtSecurityCode{padding-bottom:8px !important}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active .stxtSecurityCode+.hoverToolTip{bottom:33px}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active .qd.textPaymentInputWrapper.stxtSecurityCode{padding-bottom:4px !important}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active .stxtSecurityCode+.hoverToolTip{bottom:37px !important}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active .securityCodeValidator{top:-39px !important}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active>div.expirationContainer>.svalExpiration{top:-46px !important}.device-tablet .LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active .securityCodeValidator{top:-15px !important}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active>div.expirationContainer>.svalExpiration{margin:0}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active>div.expirationContainer>div.expirationSelectContainer{padding-bottom:30px !important}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active>div.expirationContainer{top:0 !important}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25>div.paymentInputContainer .paymentOptionControlInputContainer.creditCardEntry.active>div.expirationContainer .qd:not(.textFrag):not(.error){height:auto !important;padding-bottom:0 !important}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25 .cardSelection .paymentTypeText{font-size:18px !important;font-weight:600}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25 .cardSelection>.paymentOptionControl{margin-bottom:0 !important;box-shadow:none !important}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25 .cardSelection>.paymentOptionControl>.radioButtonImage.VI{order:1}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25 .cardSelection>.paymentOptionControl>.radioButtonImage.MC{order:2}.LD .PaymentDivStyle #wrapper #content .questionBox.sbanner .smain_table body1_25 .cardSelection>.paymentOptionControl>.radioButtonImage.AC{order:3}body .outputVersion1:not(.templated) *,body .outputVersion2:not(.templated) *{font-family:"Times New Roman",serif;font-size:12pt;line-height:18pt}body .outputVersion1:not(.templated) * ul,body .outputVersion2:not(.templated) * ul{list-style:disc}body .outputVersion1:not(.templated) * .lh,body .outputVersion1:not(.templated) * .lhl,body .outputVersion2:not(.templated) * .lh,body .outputVersion2:not(.templated) * .lhl{list-style:none}body .outputVersion1:not(.templated) * strong,body .outputVersion2:not(.templated) * strong{font-weight:700}body .outputVersion1:not(.templated) ol,body .outputVersion2:not(.templated) ol{list-style:decimal}body .outputVersion1:not(.templated) ol ol,body .outputVersion2:not(.templated) ol ol{list-style:lower-alpha}body .outputVersion1:not(.templated) ol ol ol,body .outputVersion2:not(.templated) ol ol ol{list-style:lower-roman}
			
            @media only screen and (max-device-width: 1024px) {body{ -webkit-text-size-adjust:100%; -moz-text-size-adjust:100%; -ms-text-size-adjust:100%; }}</style>
            </head>
            <body>
            <div id="outputPage" class="ContractText">
            <div class="format-html">
		<div data-exp="simple2" class="outputVersion1">
			<div class=" header">
	<span class="content">Website Privacy Policy</span>
	<span class="pageNumbers">Page <span class="currentPageNum"></span> of <span class="totalPageNum"></span></span></div><div class=" footer"></div><div class=" firstHeader"></div><div class=" firstFooter">
	<span class="pageNumbers">Page <span class="currentPageNum"></span> of <span class="totalPageNum"></span></span></div>
	<p style="text-align:center;" class="documentTitle"><strong>gateway.ireceptor.org Privacy Policy</strong>
	</p>
	<p style="text-align:center;">Type of website: Data discovery web portal<br>Effective date: 21st day of August, 2026
	</p>
	<p style="text-align:left;">gateway.ireceptor.org (the "Site") is owned and operated by iReceptor Genomic Services. iReceptor Genomic Services is the data controller and can be contacted at: <br><br>support@ireceptor.org<br>(778) 636-6560<br>________________________________________
	</p>
	<p style="text-align:left;"><strong><u>Purpose</u></strong><br>The purpose of this privacy policy (this "Privacy Policy") is to inform users of our Site of the following: 
	</p>
	<ol start="1">
		<li value="1"><span>The personal data we will collect;</span><br>
		</li>
		<li value="2"><span>Use of collected data;</span><br>
		</li>
		<li value="3"><span>Who has access to the data collected; and</span><br>
		</li>
		<li value="4"><span>The rights of Site users.</span><br>
		</li>
	</ol>
	<p style="text-align:left;">This Privacy Policy applies in addition to the terms and conditions of our Site.
	</p><div>
	<p style="text-align:left;"><strong><u>GDPR</u></strong><br>For users in the European Union, we adhere to the Regulation (EU) 2016/679 of the European Parliament and of the Council of 27 April 2016, known as the General Data Protection Regulation (the "GDPR"). For users in the United Kingdom, we adhere to the GDPR as enshrined in the Data Protection Act 2018.
	</p></div><div>
	<p style="text-align:left;"><strong><u>Consent</u></strong><br>By using our Site users agree that they consent to:
	</p>
	<ol start="1">
		<li value="1"><span>The conditions set out in this Privacy Policy.</span><br>
		</li>
	</ol></div><div>
	<p style="text-align:left;"><strong><u>Legal Basis for Processing</u></strong><br>We collect and process personal data about users in the EU only when we have a legal basis for doing so under Article 6 of the GDPR. <br><br>We rely on the following legal basis to collect and process the personal data of users in the EU:
	</p>
	<ol start="1">
		<li value="1"><span>Processing of user personal data is necessary for us or a third pary to pursue a legitimate interest. Our legitimate interest is not overriden by the interests or fundamenal rights and freedoms of users. Our legitimate interest(s) are: Managing user accounts, user billing, user support.</span><br>
		</li>
	</ol></div>
	<p style="text-align:left;"><strong><u>Personal Data We Collect</u></strong><br>We only collect data that helps us achieve the purpose set out in this Privacy Policy. We will not collect any additional data beyond the data listed below without notifying you first.<br>
	</p>
	<p style="text-align:left;"><u>Data Collected Automatically</u><br>When you visit and use our Site, we may automatically collect and store the following information:
	</p>
	<ol start="1">
		<li value="1"><span>IP address;</span><br>
		</li>
		<li value="2"><span>Clicked links; and</span><br>
		</li>
		<li value="3"><span>Content viewed.</span><br>
		</li>
	</ol>
	<p style="text-align:left;"><u>Data Collected in a Non-Automatic Way</u><br>We may also collect the following data when you perform certain functions on our Site:
	</p>
	<ol start="1">
		<li value="1"><span>First and last name;</span><br>
		</li>
		<li value="2"><span>Email address;</span><br>
		</li>
		<li value="3"><span>Phone number;</span><br>
		</li>
		<li value="4"><span>Address;</span><br>
		</li>
		<li value="5"><span>Payment information; and</span><br>
		</li>
		<li value="6"><span>User institution/company.</span><br>
		</li>
	</ol>
	<p style="text-align:left;">This data may be collected using the following methods:
	</p>
	<ol start="1">
		<li value="1"><span>Account creation.</span><br>
		</li>
	</ol>
	<p style="text-align:left;"><strong><u>How We Use Personal Data</u></strong><br>Data collected on our Site will only be used for the purposes specified in this Privacy Policy or indicated on the relevant pages of our Site. We will not use your data beyond what we disclose in this Privacy Policy.<br><br>The data we collect automatically is used for the following purposes:
	</p>
	<ol start="1">
		<li value="1"><span>Performance monitoring; and</span><br>
		</li>
		<li value="2"><span>Site optimization.</span><br>
		</li>
	</ol>
	<p style="text-align:left;">The data we collect when the user performs certain functions may be used for the following purposes:
	</p>
	<ol start="1">
		<li value="1"><span>Account management;</span><br>
		</li>
		<li value="2"><span>Billing; and</span><br>
		</li>
		<li value="3"><span>User support.</span><br>
		</li>
	</ol>
	<p style="text-align:left;"><strong><u>Who We Share Personal Data With</u></strong><br><u>Employees</u><br>We may disclose user data to any member of our organization who reasonably needs access to user data to achieve the purposes set out in this Privacy Policy.
	</p><div>
	<p style="text-align:left;"><u>Other Disclosures</u><br>We will not sell or share your data with other third parties, except in the following cases:
	</p>
	<ol start="1">
		<li value="1"><span>If the law requires it;</span><br>
		</li>
		<li value="2"><span>If it is required for any legal proceeding;</span><br>
		</li>
		<li value="3"><span>To prove or protect our legal rights; and</span><br>
		</li>
		<li value="4"><span>To buyers or potential buyers of this company in the event that we seek to sell the company.</span><br>
		</li>
	</ol>
	<p style="text-align:left;">If you follow hyperlinks from our Site to another site, please note that we are not responsible for and have no control over their privacy policies and practices.
	</p></div>
	<p style="text-align:left;"><strong><u>How Long We Store Personal Data</u></strong><br>User data will be stored until the purpose the data was collected for has been achieved.<br><br>You will be notified if your data is kept for longer than this period.
	</p>
	<p style="text-align:left;"><strong><u>How We Protect Your Personal Data</u></strong><br>In order to protect your security, we use the strongest available browser encryption and store all of our data on servers in secure facilities. All data is only accessible to our employees. Our employees are bound by strict confidentiality agreements and a breach of this agreement would result in the employee's termination.<br><br>While we take all reasonable precautions to ensure that user data is secure and that users are protected, there always remains the risk of harm. The Internet as a whole can be insecure at times and therefore we are unable to guarantee the security of user data beyond what is reasonably practical.
	</p>
	<p style="text-align:left;"><strong><u>Your Rights as a User</u></strong><br>Under the GDPR, you have the following rights:
	</p>
	<ol start="1">
		<li value="1"><span>Right to be informed;</span><br>
		</li>
		<li value="2"><span>Right of access;</span><br>
		</li>
		<li value="3"><span>Right to rectification;</span><br>
		</li>
		<li value="4"><span>Right to erasure;</span><br>
		</li>
		<li value="5"><span>Right to restrict processing;</span><br>
		</li>
		<li value="6"><span>Right to data portability; and</span><br>
		</li>
		<li value="7"><span>Right to object.</span><br>
		</li>
	</ol><div>
	<p style="text-align:left;"><strong><u>Children</u></strong><br>The minimum age to use our website is 16 years of age. We do not knowingly collect or use personal data from children under 16 years of age. If we learn that we have collected personal data from a child under 16 years of age, the personal data will be deleted as soon as possible. If a child under 16 years of age has provided us with personal data their parent or guardian may contact our data protection officer.
	</p></div>
	<p style="text-align:left;"><strong><u>How to Access, Modify, Delete, or Challenge the Data Collected</u></strong><br>If you would like to know if we have collected your personal data, how we have used your personal data, if we have disclosed your personal data and to who we disclosed your personal data, if you would like your data to be deleted or modified in any way, or if you would like to exercise any of your other rights under the GDPR, please contact our data protection officer here:<br><br>Brian Corrie<br>brian.corrie@ireceptor-gs.com<br>(778) 636-6560<br>________________________________________
	</p><div>
	<p style="text-align:left;"><strong><u>Do Not Track Notice</u></strong><br>Do Not Track ("DNT") is a privacy preference that you can set in certain web browsers. We do not track the users of our Site over time and across third party websites and therefore do not respond to browser-initiated DNT signals.
	</p></div>
	<p style="text-align:left;"><strong><u>Modifications</u></strong><br>This Privacy Policy may be amended from time to time in order to maintain compliance with the law and to reflect any changes to our data collection process. When we amend this Privacy Policy we will update the "Effective Date" at the top of this Privacy Policy. We recommend that our users periodically review our Privacy Policy to ensure that they are notified of any updates. If necessary, we may notify users by email of changes to this Privacy Policy.
	</p>
	<p style="text-align:left;"><strong><u>Complaints</u></strong><br>If you have any complaints about how we process your personal data, please contact us through the contact methods listed in the <em>Contact Information</em> section so that we can, where possible, resolve the issue. If you feel we have not addressed your concern in a satisfactory manner you may contact a supervisory authority. You also have the right to directly make a complaint to a supervisory authority. You can lodge a complaint with a supervisory authority by contacting the _____________________________________________________________________________.
	</p>
	<p style="text-align:left;"><strong><u>Contact Information</u></strong><br>If you have any questions, concerns or complaints, you can contact our data protection officer, Brian Corrie, at:<br><br>brian.corrie@ireceptor-gs.com<br>(778) 636-6560<br>________________________________________
	</p><div class="LDCopyright">
				<p>&copy;2002-2026 LawDepot.ca&reg;</p>
			</div>
		</div>
	</div>
            </div>
		</div>
	</div>

</div>
@stop

	
