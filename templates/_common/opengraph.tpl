{if !empty($og)}

    <meta property="og:url" content="{$og.url}">
    <meta property="og:type" content="{$og.type|default:'website'}">
    <meta property="og:title" content="{$og.title}">
    <meta property="og:description" content="{$og.description}">
    <meta property="og:image" content="{$og.image}">
    <meta property="og:logo" content="{$og.image}">
    <meta property="og:site_name" content="{$og.title}">
    <meta property="twitter:domain" content="{$og.domain}">
    <meta property="twitter:url" content="{$og.url}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{$og.title}">
    <meta name="twitter:description" content="{$og.description}">
    <meta name="twitter:image" content="{$og.image}">
{/if}