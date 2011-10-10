<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
	<title>{$feedName}</title>
	<description>{$feedDescription}</description>
	<link>{$showsLink}</link>
	<atom:link href="{$feedLink}" rel="self" type="application/rss+xml" />
	<language>en-gb</language>
	<copyright>{$siteCopyright}</copyright>
	<lastBuildDate>{$feedDate}</lastBuildDate>
	<pubDate>{$feedDate}</pubDate>
	<docs>http://blogs.law.harvard.edu/tech/rss</docs>
	<image>
		<url>{$baseURL}images/cchits_coverart.png</url>
		<title>{$feedName}</title>
		<link>{$showsLink}</link>
		<width>144</width>
		<height>144</height>
	</image>
	<managingEditor>{$feedOwner}</managingEditor>
	<webMaster>{$feedOwner}</webMaster>
{foreach from=$chart.position key=position item=track}
{if $track.strTrackName != ''}
	<item>
		<title>{$track.strTrackName}</title>
		<link>{$baseURL}track/{$track.intTrackID}</link>
		<guid isPermaLink="false">{$baseURL}chart/{$chart.intChartDate}#{$track.intTrackID}</guid>
		<description><![CDATA[<p>{$track.intChartPosition}: {$track.strTrackName} by {$track.strArtistName}</p>
<p>Due to:</p>
<ul>
{if isset($track.reasons.vote)}<li>This track received votes today</li>{/if}
{if isset($track.reasons.show)}<li>This track was listed in a show today</li>{/if}
{if isset($track.reasons.move)}<li>This track moved in the charts today</li>{/if}
</ul>
]]></description>
		<pubDate>{$chart.intChartDate}</pubDate>
		<author>{$feedOwner}</author>
	</item>
{/if}
{/foreach}
	</channel>
</rss>