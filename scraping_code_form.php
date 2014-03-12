<?php

/*  Dependency: Scraping_Code  */

class Scraping_Code_Form extends Scraping_Code {
	
	public function getFormValues() {
		
		$form_name_values_data = array();
		$subject = $this->getSubject();
		
		if($this->matchAll('|(<form[^>]*>).*?</form>|i', $matches_forms)) {
			
			$matches_forms_count = count($matches_forms[0]);
			
			for ($i = 0; $i < $matches_forms_count; $i++) {
				
				$form_name_values = array();
				$matches_form = $matches_forms[0][$i];
				$this->setSubject($matches_form);
				$form_tag = $matches_forms[1][$i];
				$form_name = '';
				
				if($this->getPropertyValues(array('name'), $form_tag, $property_values)) {
						
					$form_name = $property_values['name'];
						
				}
				
				$combo_match_params = array(
		
						$this->comboMatchAllParam('|<input[^>]*>|i', array(
								'input_tags' => 0
						)),
						$this->comboMatchAllParam('|(<select[^>]*>).*?</select>|i', array(
								'select_blocks' => 0, 
								'select_tags' => 1
						)),
						$this->comboMatchAllParam('|(<textarea[^>]*>)(.*?)</textarea>|i', array(
								'textarea_tags' => 1, 
								'textarea_values' => 2
						))
							
				);
				
				if($this->comboMatch($combo_match_params, false, $matches_form_tags)) {
				
					$input_tags = $matches_form_tags['input_tags'];
					
					foreach ($input_tags as $input_tag) {

						if($this->getPropertyValues(array('type', 'name', 'value'), $input_tag, $property_values)) {
							
							$input_name = $property_values['name'];
							$input_type = $property_values['type'];
							$input_value = $property_values['value'];
							
							if($input_type == 'text' 
									|| $input_type == 'hidden' 
									|| $input_type == 'submit') {
								
								if($input_name != '') {
									
									$form_name_values[] = $this->getFormNameValue($input_name, $input_value);
									
								}
								
							} else if($input_type == 'radio' && $this->isChecked($input_tag)) {
								
								$form_name_values[] = $this->getFormNameValue($input_name, $input_value);
									
							} else if($input_type == 'checkbox' && $this->isSelected($input_tag)) {
								
								$form_name_values[] = $this->getFormNameValue($input_name, $input_value);
								
							}
							
						}
						
					}
				
				}
		
				$select_tags = $matches_form_tags['select_tags'];
				$select_blocks = $matches_form_tags['select_blocks'];
				$select_default_name_value = '';
				
				foreach ($select_tags as $index => $select_tag) {
					
					if($this->getPropertyValues(array('name'), $select_tag, $property_values)
							&& preg_match_all('|<option[^>]*>|i', $select_blocks[$index], $matches_3)) {
						
						$select_name = $property_values['name'];
						$option_tags = $matches_3[0];
						$option_tags_count = count($option_tags);
						$add_flag = false;
						
						for ($j = 0; $j < $option_tags_count; $j++) {
							
							$option_tag = $option_tags[$j];
							
							if($this->getPropertyValues(array('value'), $option_tag, $property_values)) {
								
								$select_value = $property_values['value'];
								
								if($this->isSelected($option_tag) ) {
									
									$form_name_values[] = $this->getFormNameValue($select_name, $select_value);
									$add_flag = true;
									break;
									
								} else if($j == 0) {

									$select_default_name_value = $this->getFormNameValue($select_name, $select_value);
									
								}
								
							}
							
						}
						
						if(!$add_flag) {
							
							$form_name_values[] = $select_default_name_value;
							
						}
						
					}
					
				}
				
				$textarea_tags = $matches_form_tags['textarea_tags'];
				$textarea_values = $matches_form_tags['textarea_values'];
				
				foreach ($textarea_tags as $index => $textarea_tag) {
					
					if($this->getPropertyValues(array('name'), $textarea_tag, $property_values)) {
						
						$textarea_name = $property_values['name'];
						$textarea_value = $textarea_values[$index];
						$form_name_values[] = $this->getFormNameValue($textarea_name, $textarea_value);
						
					}
					
				}
			
				$form_values = array();
				$query = implode('&', $form_name_values);
				parse_str($query, $form_values);
				
				if($form_name != '') {
					
					$form_name_values_data[$form_name] = $form_values;
					
				} else {
					
					$form_name_values_data[] = $form_values;
					
				}
				
			}
			
			$this->setSubject($subject);
			return $form_name_values_data;
		
		}
		
		return $form_values;
		
	}
	
	private function getPropertyValues($property_names, $tag, &$property_values) {
		
		$property_values = array();
		
		if(preg_match_all('!('. implode('|', $property_names) .')="([^"]*)"!i', $tag, $matches)) {
				
			$matches_count = count($matches[0]);
				
			for ($i = 0; $i < $matches_count; $i++) {
		
				$property_name = strtolower($matches[1][$i]);
				$property_value = $matches[2][$i];
				$property_values[$property_name] = $property_value;
		
			}
			
			return true;
			
		}
		
		return false;
		
	}
	
	private function isSelected($tag) {
		
		return $this->isTagAvailable('selected', $tag);
		
	}
	
	private function isChecked($tag) {
		
		return $this->isTagAvailable('checked', $tag);
		
	}
	
	private function isTagAvailable($mode, $tag) {
		
		return (preg_match('|\s+'. $mode .'|i', $tag) || preg_match('|'. $mode .'="'. $mode .'"|i', $tag));
		
	}
	
	private function getFormNameValue($name, $value) {
		
		return $name .'='. $value;
		
	}
	
}
/*** Example

	require_once 'scraping_code.php';
	require_once 'scraping_code_form.php';
	
	$sc = new Scraping_Code_Form();
	
	$sc->setSubject($subject);
	$form_values = $sc->getFormValues();
	print_r($form_values);

***/
