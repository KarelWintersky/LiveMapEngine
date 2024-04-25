{if !empty($og)}
    <meta name="description" content="{$og.title}">

    <meta property="og:url" content="{$og.url}">
    <meta property="og:type" content="{$og.type|default:'website'}">
    <meta property="og:title" content="{$og.title}">
    <meta property="og:description" content="{$og.description}">
    <meta property="og:image" content="{$og.image}">
    {*
    <meta property="og:image:width"" content="3094">
    <meta property="og:image:height"" content="2480">
    *}

    <meta property="og:logo" content="{$og.image}">
    <meta property="og:site_name" content="{$og.title}">

    <meta property="twitter:domain" content="{$og.domain}">
    <meta property="twitter:url" content="{$og.url}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{$og.title}">
    <meta name="twitter:description" content="{$og.description}">
    <meta name="twitter:image" content="{$og.image}">
{/if}