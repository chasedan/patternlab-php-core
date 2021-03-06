<?php

/*!
 * Pattern Data Pattern Partials Exporter Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Generates the partials to be used in the viewall & styleguide
 *
 */

namespace PatternLab\PatternData\Exporters;

use \PatternLab\Config;
use \PatternLab\Data;
use \PatternLab\PatternData;
use \PatternLab\Timer;

class PatternPartialsExporter extends \PatternLab\PatternData\Exporter {
	
	protected $store;
	protected $cacheBuster;
	protected $styleGuideExcludes;
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		$this->store       = PatternData::get();
		$this->cacheBuster = Data::getOption("cacheBuster");
		$this->styleGuideExcludes = Config::getOption("styleGuideExcludes");
		
	}
	
	/**
	* Compare the search and ignore props against the name.
	* Can use && or || in the comparison
	* @param  {String}       the type of the pattern that should be used in the view all
	* @param  {String}       the subtype of the pattern that be used in the view all
	*
	* @return {Array}        the list of partials
	*/
	public function run($type = "", $subtype = "") {
		
		// default vars
		$patternPartials    = array();
		$suffixRendered     =	Config::getOption("outputFileSuffixes.rendered");
		
		foreach ($this->store as $patternStoreKey => $patternStoreData) {

      // Docs for patternTypes (i.e. `atoms.md`), don't have these rules and need them to pass below conditionals
		  if (
		      !isset($patternStoreData['depth'])
          && !isset($patternStoreData['hidden'])
          && !isset($patternStoreData['noviewall'])
      ) {
		    $patternStoreData["hidden"] = false;
		    $patternStoreData["noviewall"] = false;
		    $patternStoreData["depth"] = 0;
      }
      $canShow = isset($patternStoreData["hidden"]) && (!$patternStoreData["hidden"]) && (!$patternStoreData["noviewall"]);

      if (($patternStoreData["category"] == "pattern") && $canShow && ($patternStoreData["depth"] > 1) && (!in_array($patternStoreData["type"],$this->styleGuideExcludes))) {
				
				if ((($patternStoreData["type"] == $type) && empty($subtype)) || (empty($type) && empty($subtype)) || (($patternStoreData["type"] == $type) && ($patternStoreData["subtype"] == $subtype))) {
					
					$patternPartialData                            = array();
					$patternPartialData["patternName"]             = $patternStoreData["nameClean"];
					$patternPartialData["patternLink"]             = $patternStoreData["pathDash"]."/".$patternStoreData["pathDash"].$suffixRendered.".html";
					$patternPartialData["patternPartial"]          = $patternStoreData["partial"];
					$patternPartialData["patternPartialCode"]      = $patternStoreData["code"];
					$patternPartialData["patternState"]            = $patternStoreData["state"];
					
					$patternPartialData["patternLineageExists"]    = isset($patternStoreData["lineages"]);
					$patternPartialData["patternLineages"]         = isset($patternStoreData["lineages"]) ? $patternStoreData["lineages"] : array();
					$patternPartialData["patternLineageRExists"]   = isset($patternStoreData["lineagesR"]);
					$patternPartialData["patternLineagesR"]        = isset($patternStoreData["lineagesR"]) ? $patternStoreData["lineagesR"] : array();
					$patternPartialData["patternLineageEExists"]   = (isset($patternStoreData["lineages"]) || isset($patternStoreData["lineagesR"]));
					
					$patternPartialData["patternDescExists"]       = isset($patternStoreData["desc"]);
					$patternPartialData["patternDesc"]             = isset($patternStoreData["desc"]) ? $patternStoreData["desc"] : "";
					
					$patternPartialData["patternDescAdditions"]    = isset($patternStoreData["partialViewDescAdditions"]) ? $patternStoreData["partialViewDescAdditions"] : array();
					$patternPartialData["patternExampleAdditions"] = isset($patternStoreData["partialViewExampleAdditions"]) ? $patternStoreData["partialViewExampleAdditions"] : array();
					
					// add the pattern data so it can be exported
					$patternData                                   = array();
					$patternData["lineage"]                        = isset($patternStoreData["lineages"])  ? $patternStoreData["lineages"] : array();
					$patternData["lineageR"]                       = isset($patternStoreData["lineagesR"]) ? $patternStoreData["lineagesR"] : array();
					$patternData["patternBreadcrumb"]              = $patternStoreData["breadcrumb"];
					$patternData["patternDesc"]                    = (isset($patternStoreData["desc"])) ? $patternStoreData["desc"] : "";
					$patternData["patternExtension"]               = Config::getOption("patternExtension");
					$patternData["patternName"]                    = $patternStoreData["nameClean"];
					$patternData["patternPartial"]                 = $patternStoreData["partial"];
					$patternData["patternState"]                   = $patternStoreData["state"];
					$patternPartialData["patternData"]             = json_encode($patternData);
					
					$patternPartials[]                             = $patternPartialData;
				
				}
				
			} else if (($patternStoreData["category"] == "patternSubtype") && (!in_array($patternStoreData["type"],$this->styleGuideExcludes))) {
				
				if ((($patternStoreData["type"] == $type) && empty($subtype)) || (empty($type) && empty($subtype)) || (($patternStoreData["type"] == $type) && ($patternStoreData["name"] == $subtype))) {
					
					$patternPartialData                            = array();
					$patternPartialData["patternName"]             = $patternStoreData["nameClean"];
					$patternPartialData["patternLink"]             = $patternStoreData["pathDash"]."/index.html";
					$patternPartialData["patternPartial"]          = $patternStoreData["partial"];
					$patternPartialData["patternSectionSubtype"]   = true;
					$patternPartialData["patternDesc"]             = isset($patternStoreData["desc"]) ? $patternStoreData["desc"] : "";
					
					$patternPartials[] =  $patternPartialData;
					
				}

      } else if (($patternStoreData["category"] == "pattern") && $canShow && (isset($patternStoreData["full"]) && ($type === $patternStoreData["full"] || $type === ""))) {
        // This is for `patternType` docs. Given this structure:
        // - _patterns/
        //   - atoms/
        //     - forms/
        //   - atoms.md
        // This will take the contents of `atoms.md` and place at top of "Atoms > View All"

        $patternPartialData = array();
        // Getting the name from md's `title: My Name` works here, as does the link, but it doesn't make sense to link to the view you are already on. Plus you can just do the title in the MD doc. Keeping here for now in case it's wanted later.
        // $patternPartialData["patternName"] = isset($patternStoreData["nameClean"]) ? $patternStoreData["nameClean"] : '';
        // $patternPartialData["patternLink"] = $patternStoreData["full"] . "/index.html";

        $patternPartialData["patternSectionSubtype"] = true;
        $patternPartialData["patternDesc"] = isset($patternStoreData["desc"]) ? $patternStoreData["desc"] : "";

        $patternPartials[] = $patternPartialData;
      }
			
		}
		
		return array("partials" => $patternPartials, "cacheBuster" => $this->cacheBuster);
		
	}
	
}
