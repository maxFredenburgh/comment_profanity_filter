<?php

namespace Drupal\comment_profanity_filter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Class DefaultForm.
 */
class DefaultForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commentProfanityForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      $default_filename='public://comment_profanity_filter/default_list.txt';
      $defaultWords='';
      if(file_exists($default_filename)){
          $myDefaultFile=fopen($default_filename,"r");
          $defaultWords = file($default_filename, FILE_IGNORE_NEW_LINES);
      }

      $filename = 'public://comment_profanity_filter/custom_list.txt';
      $myWords='';
      if(file_exists($filename)){
          $myFile=fopen($filename,"r");
          $myWords = file($filename, FILE_IGNORE_NEW_LINES);
      }
      $default_rows = "";

      foreach($defaultWords as $badWord){
          $default_rows.=$badWord."<br>";
      }

      $form['default_list'] = array(
          '#type' => 'radios',
          '#title' => $this
              ->t('Include default list?'),
          '#default_value' => 0,
          '#options' => array(
              0 => $this
                  ->t('Don\'t include'),
              1 => $this
                  ->t('Include'),
          ),
      );

      $form['text_details']['#markup'] = t('<details><summary>Click here to view the default list</summary>'.$default_rows.'</details>');
      $form['text_list_title']['#markup'] = t('<h3>Current list of banned words/phrases (select a word to remove it)</h3>');


      $current_rows[]=array();

      $count=1;
      foreach($myWords as $badWord){
            $options[$badWord]= ['words' => $badWord,];
            $count++;
          //$current_rows[] = array($badWord);
      }
      $header = [
          'words' => $this
              ->t('Bad word'),
      ];

      $form['table'] = array(
          '#type' => 'tableselect',
          '#header' => $header,
          '#options' => $options,
          '#empty' => $this
              ->t('You have not added any words yet'),
      );
/*
 * Add this block depending on filter method. If only matching words, do not add. If matching by string/sequence then add.
      $style="border: red solid; border-radius: 5px; padding:10px;";
      $form['text_warning']['#markup'] =
       t('<div style="'.$style.'"><h3 style="color: red">WARNING!</h3><br> Adding words that are common in other words will still block comments!<br>
       For example, banning \'ass\' will block comments with the word \'classic\'. Be careful with what you ban!</div><br> ');
      $form['text_info']['#markup'] = t('<p>Not case sensitive</p>');
*/


      $form['addWords'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Add a word or words separated by line'),
      ];

    fclose($myFile);
    fclose($myDefaultFile);

      $form['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Update List'),
      ];
    return $form;
  }

  public function updateList(array &$form, FormStateInterface &$form_state) {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state){
      $wordsToAdd = $form_state->getValue('addWords');
      $default_list_include=$form_state->getValue('default_list');
      $wordsToRemove = array_filter($form_state->getValue('table'));

      $filename = 'public://comment_profanity_filter/custom_list.txt';
      $currentListFile=fopen($filename,"r");
      $currentWords = file($filename, FILE_IGNORE_NEW_LINES);
      fclose($currentListFile);
      $result=$currentWords;

      if($default_list_include==1){
          $default_filename = 'public://comment_profanity_filter/default_list.txt';
          $defaultListFile=fopen($default_filename,"r");
          $defaultWords = file($default_filename, FILE_IGNORE_NEW_LINES);
          fclose($defaultListFile);
          $result=array_merge($result,$defaultWords);
      }

      if(!empty($wordsToAdd)){
          $wordsToAdd =explode("\n", $wordsToAdd);
          $result=array_merge($result,$wordsToAdd);
      }

      if(!empty($wordsToRemove)){
          foreach ($wordsToRemove as $del_word){
              if (($key = array_search($del_word, $result)) !== false) {
                  unset($result[$key]);
              }
          }
      }

      $currentListFile=fopen($filename,"w");
      if($currentListFile){
          foreach ($result as $badWord){
              fwrite($currentListFile, $badWord."\n");
          }
          drupal_set_message('List has been updated!');
          fclose($currentListFile);
      }else{
          drupal_set_message('Failed to open \''.$filename.'\' to write','error');
      }



  }
}
