<?php

namespace Livemap\Units;

use Livemap\Template;

class MapRender
{
    private $map_alias;
    private $map_config;
    
    public function __construct($map_alias, $map_config)
    {
        $this->unit = new Map();
        
        $this->map_alias = $map_alias;
        $this->map_config = $map_config;
        
        
        Template::assign('map_alias', $map_alias);
    
        if (!empty($this->map_config->display->custom_css)) {
            Template::assign('custom_css', "/storage/{$this->map_alias}/styles/{$this->map_config->display->custom_css}");
        }
    
        Template::assign('panning_step', $this->map_config->display->panning_step ?? 70);
        Template::assign('html_title', $this->map_config->title);
        Template::assign('html_callback', '/');
    }
    
    public function view($view_mode = 'folio')
    {
        // извлекаетм все регионы с информацией
        $this->map_regions_with_info = $this->unit->getRegionsWithInfo( $this->map_alias );
    
        // фильтруем по доступности пользователю (is_publicity)
        $this->map_regions_with_info = $this->unit->checkRegionsVisibleByUser($this->map_regions_with_info, $this->map_alias);
    
        // фильтруем по visibility
        $this->map_regions_with_info = $this->unit->removeExcludedFromRegionsList($this->map_regions_with_info);
    
        Template::assign('regions_with_content_ids', $this->lme->convertRegionsWithInfo_to_IDs_String($this->map_regions_with_info));
        
        switch ($viewmode) {
            case 'iframe': {
                $this->makemap_iframe_colorbox();
                break;
            }
        
            case 'iframe:colorbox': {
                $this->makemap_iframe_colorbox();
                break;
            }
        
            case 'folio': {
                $this->makemap_folio();
                break;
            }
        
            case 'wide:infobox>regionbox': {
                $this->makemap_fullscreen('infobox>regionbox');
                break;
            }
        
            case 'wide:regionbox>infobox': {
                $this->makemap_fullscreen('regionbox>infobox');
                break;
            }
        
            default: {
                $this->makemap_404( $viewmode );
                die( $this->template->render() );
            }
        } // switch $viewmode
    
        return true;
    }
    
    
    
}
    
}