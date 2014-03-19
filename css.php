<?php

class PHPEffectiveCss {


    public function apply_css(DOMDocument $xml, $css) {
        $this->apply_styles($xml, $css);

    }
    protected function add_style(&$styles, $weight, $rule) {
          if (!isset($styles[$rule->getRule()])) {
            $styles[ $rule->getRule() ] = array(
                'weight' => $weight,
                'rule'   => $rule
            );
            return;
          }

          if ($weight > $styles[ $rule->getRule() ]['weight'] || $rule->getIsImportant()) {
              // the rule is not important, so we override it, no matter if the new rule is important or selector
              // is with greater weight
              if ( $styles[ $rule->getRule() ]['rule']->getIsImportant() === false) {
                $styles[ $rule->getRule() ] = array(
                    'weight' => $weight,
                    'rule'   => $rule
                );
                return;
            }
            // the new rule is important as well, so if the selector is with greater weight override
            if ( $weight > $styles[ $rule->getRule() ]['weight'] && $rule->getIsImportant() ) {
                $styles[ $rule->getRule() ] = array(
                    'weight' => $weight,
                    'rule'   => $rule
                );
                return;
            }
          }
    }

    protected function get_effective_styles($data, $node) {
        $styles = array();
        foreach ($data as $entry) {
            $selector = $entry['selector'];
            $rules = $entry['rules'];

            foreach ($rules as $rule) {
                $this->add_style($styles, $selector->getSpecificity(), $rule);
            }
        }

        $rules = array();
        foreach ($styles as $style) {
            $rules[] = $style['rule']->__toString();
        }

        return implode($rules);
    }

    protected function apply_styles($xml, $css) {
        $guid = 0;
        $styles = array();
        $engine = new SelectorDOM($xml);
        $processed_nodes = array();

        $parser = new \Sabberworm\CSS\Parser($css);
        $cssParser = $parser->parse();
        foreach ($cssParser->getAllRuleSets() as $ruleset) {
            $selectors = $ruleset->getSelectors();
            foreach ($selectors as $selector) {
               $string = $selector->getSelector();
               $nodes = @$engine->select($string, $as_array = false);
               if ($nodes === false) {
                   continue;
               }
               for ($i = 0; $i < $nodes->length; $i++) {
                   $node = $nodes->item($i);
                   // assign id so we know this node already exists
                   $id = $node->getAttribute('data-guid');
                   if (!$id) {
                       $id = ++$guid;
                       $node->setAttribute('data-guid', $id);
                       $processed_nodes[] = $node;
                   }
                   $styles[$id][] = array('selector' => $selector,
                                          'rules'    => $ruleset->getRules()
                                        );

               }
            }
        }

        foreach ($processed_nodes as $node) {
            $guid = $node->getAttribute('data-guid');
            $css = $this->get_effective_styles($styles[$guid], $node);
            $node->setAttribute('style', $css);
            $node->removeAttribute('data-guid');
        }
    }
}
