<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/_.php");
includeOnce("pages/page.php");
includeOnce("core/queries/SurahInfoQueries.php");

$_GET["spron"] = true;
$randomQuote = includeContents("pages/embedded/contents/random-quran.php");
$extraHeadHtml = <<<HEAD
<meta name="google-site-verification" content="1tPQ-pqnEzNEdMsatRSvw9K3WpwlnIQjZJCtaHMIgWw" />
<link rel="search" type="application/opensearchdescription+xml" href="/search/opensearch.xml" title="Amrayn.com" />

	<script type="application/ld+json">
	{
	   "@context": "http://schema.org",
	   "@type": "WebSite",
	   "url": "http://amrayn.com/",
	   "potentialAction": {
	     "@type": "SearchAction",
	     "target": "http://amrayn.com/search?q={search_term_string}",
	     "query-input": "required name=search_term_string"
	   }
	}
	</script>
HEAD;
$contents = <<<PAGE
	<style type='text/css'>
	@media(max-width: 820px) {
		.search-bar,.empty-line-for-search-bar { display: none; }
		#body-contents {
			margin-top: 0px;
		}
	}
    .juz-head:before {
		content:"Juz ";
	}
	.juz-head {
		background-color: #ccc;
		  font-weight: bold;
		  color: #333;
	}
	</style>
<blockquote class='front-page'>
$randomQuote
</blockquote><br/>
<center>
		<input placeholder='Search Quran...' type='text' name='search-bar2' id='search-bar2' value='' class='rounded search search-bar2 secondary-search-bar' style='float:none !important;outline-width: 0;font-size: 1.3em;border: 1px solid #999;width:76%;box-shadow: 0px 0px 2pt 1pt #ccc;' />
	</center><br/>
<h1>Read Qur&#39;an</h1>
<div class='chap-links quran-links-container'>
PAGE;
$allSurahs = SurahInfoQueries::queryByTypes(array(SurahInfoTypes::SurahNameMeaning, SurahInfoTypes::SurahNameEnglishPronounced, SurahInfoTypes::RevelationPeriod, SurahInfoTypes::VerseCount));

for ($i = 1; $i <= 114; ++$i) {
	$period = SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::RevelationPeriod, $i, $allSurahs, true);
	$revelationClass = $period == 2 ? "makka" : "medina";
	$englishPron = SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::SurahNameEnglishPronounced, $i, $allSurahs, true);
	$meaning = SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::SurahNameMeaning, $i, $allSurahs, true);
	$verseCount = SurahInfo::getInfoByTypeAndSurah(SurahInfoTypes::VerseCount, $i, $allSurahs, true);
	$contents .= <<<PAGE
		<div class='list-link-container quran-links-container'>
			<a class='list-link-item local $revelationClass' href='/$i'>
				<div>
					<span class='link-info-title'>$i. $englishPron</span>
					<br class='hide-on-small-devices'/>
					<span class='hide-on-small-devices link-info'>$meaning</span>
					<br class='hide-on-small-devices'/>
					<span class='hide-on-small-devices link-info'>$verseCount Verses</span>
				</div>
			</a>
			<a class='link-small-icon fa fa-book local' title='Open in E-Mushaf' style='right:30px' href='/quran/$i'></a>
			<a class='link-small-icon fa fa-info-circle local' style='right:0px' title='Surah Info' href='/quran/info/$i'></a>
		</div>
PAGE;
}
$contents .= <<<PAGE
</div>
PAGE;
// -------------------------------------------------------------------------------------
init($contents, array(
	"title" => "Noble Quran",
	"context" => Context::Quran,
	"other_metadata" => array(
		array("name" => "msvalidate.01", "content" => "C7D6A392927D885D1182A6D955896871")
	),
	"extra_head_html" => $extraHeadHtml
));
?>
