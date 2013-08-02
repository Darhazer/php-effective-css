<?php

class PHPEffectiveCss {

    protected $_xml = null;
    protected $_css = null;

    public function apply_css(DOMDocument $xml, $css) {
        $this->_xml = $xml;
        $this->_css = $this->preprocess_css($css);
        $this->apply_styles();

    }
    protected function add_style($old_properties, $new_properties) {
          $properties = array();
          $styles = explode(';', $old_properties);
          $styles = array_filter($styles);
          foreach ($styles as $key => $value) {
             list($name, $value) = explode(':', $value, 2);
             $name = trim($name);
             $properties[$name] = $value;
          }

          $styles = explode(';', $new_properties);
          $styles = array_filter($styles);
          foreach ($styles as $key => $value) {
            list($name, $value) = explode(':', $value, 2);
            $name = trim($name);
            $properties[$name] = $value;
          }


          $result = '';
          foreach ($properties as $name => $value) {
            $result .= $name.':'.$value.';';
          }
          return $result;

    }

    protected function implode_recursive($glue, $array) {
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[] = $this->implode_recursive($glue, $value);
            } else {
                $result[] = $value;
            }
        }
        return implode($glue, $result);
    }

    protected function get_effective_styles($rules, $node) {
        // calculate weights
        $weight_css = array();
        $style = '';

        foreach ($rules as $rule) {
            list($selector, $properties) = explode('|', $rule);
            if (strpos($selector, '#') !== false) {
                $weight_css['id'][$selector][] = $properties;
             } elseif (strpos($selector, '.') !== false) {
                $weight_css['class'][$selector][] = $properties;
             } else {
                $weight_css['generic'][$selector][] = $properties;
             }
        }

        if (isset($weight_css['generic'])){
            $style = $this->implode_recursive("\n", $weight_css['generic']);
        }

        if (isset($weight_css['class'])){
            $style = $this->add_style($style, $this->implode_recursive("\n", $weight_css['class']));
        }
        if (isset($weight_css['id'])){
            $style = $this->add_style($style, $this->implode_recursive("\n", $weight_css['id']));
        }

        $classes = explode(' ', $node->getAttribute('class'));
        $classes = array_filter($classes);
        foreach ($classes as $class) {
           $class = '.' . $class;
           if (isset($weight_css['class'][$class])) {
               $style = $this->add_style($style, $this->implode_recursive("\n", $weight_css['class'][$class]));
           }
        }
        $id = $node->getAttribute('id');

        if ($id) {
            $id = '#' . $id;
            if (isset($weight_css['id'][$id])) {
                $style = $this->add_style($style, implode("\n", $weight_css['id'][$id]));
            }
        }

        return $style;
    }

    protected function apply_styles() {
        $guid = 0;
        $styles = array();
        $engine = new SelectorDOM($this->_xml);
        $processed_nodes = array();

        preg_match_all('#(.*){([^}]+)}#isU', $this->_css, $rules);
        foreach ($rules[1] as $key => $rule) {

            $properties = $rules[2][$key];
            if (empty($properties)) {
                continue;
            }

            $rule = trim($rule);
            $rule = preg_replace("/\s{2,}/", '', $rule);
            $rule = trim($rule, '}');

            $nodes = @$engine->select($rule, $as_array = false);

            if ($nodes === false) {
                continue;
            }
            for ($i = 0; $i < $nodes->length; $i++) {
                $node = $nodes->item($i);
                $id = $node->getAttribute('data-guid');
                if (!$id) {
                    $id = ++$guid;
                    $node->setAttribute('data-guid', $id);
                }
                $styles[$id][] = $rule . '|' .$properties;
                $processed_nodes[] = $node;
            }
        }
        foreach ($processed_nodes as $node) {
            $guid = $node->getAttribute('data-guid');
            if (!$guid) {
                continue;
            }
            $css = $this->get_effective_styles($styles[$guid], $node);
            $node->setAttribute('style', $css);
            $node->removeAttribute('data-guid');
        }
    }

    protected function preprocess_css($content) {
        // normalize spaces
        $content = preg_replace('#\s+#', ' ', $content);

        // remove comments
        $content = preg_replace('#/\*(.*)\*/#isU', '', $content);

        // remove space after column, so you have the same parsing
        $content = str_replace(': ', ':', $content);

        // break all rules applied to multiple selectors to ones applied to single one
        // eg a, b {display: block;} becomes
        // a {display: block}
        // b {display: block}
        preg_match_all('#(.*){([^}]+)}#isU', $content, $rules);
        $ruleset = array();

        foreach ($rules[1] as $key => $rule) {
            $selectors = explode(',', $rule);
            $selectors = array_map('trim', $selectors);
            foreach ($selectors as $selector) {
                $ruleset[] = "$selector {" . $rules[2][$key] ."}";
            }
        }

        return implode("\n", $ruleset);
    }
}