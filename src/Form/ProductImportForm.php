<?php

namespace Drupal\product_importer\Form;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

class ProductImportForm extends FormBase {

    public function getFormId()
    {
        return "product_import";
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['description'] = [
            '#markup' => '<p>Use this form to upload a CSV file of Data</p>'
        ];

        $validators = [
            'file_validate_extensions' => ['csv']
        ];

        $form['import_csv'] = [
            '#type' => 'managed_file',
            '#title' => t('Upload file here:'),
            '#upload_location' => 'public://importcsv/',
            '#upload_validators' => $validators,
        ];

        $form['actions']['#type'] = 'actions';

        $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
    );
    return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $csv_file = $form_state->getValue('import_csv');

        $file = File::load($csv_file[0]);

        $file->setPermanent();          //Da bih ga sačuvao u bazi mora biti setovan na permanent.

        $file->save();

        $data = $this->csvtoarray($file->getFileUri(), ';');

        $store = Store::load('1');

        //-----------------         KREIRANJE PROIZVODA         ------------------------
        $i = 1;

        foreach($data as $item) {
            $product["product".$i] = Product::create([
                'uid' => $i,
                'type' => 'default',
                'title' => $item['Title'],
                'body' => $item['Body']
            ]);
            $variation["variation".$i] = ProductVariation::create([
                'type' => 'default',
                'sku' => $item['SKU'],
                'price' => new Price($item['Price'], 'EUR'),
                'field_stock' => $item['Lager']
            ]);

            $product["product".$i]->save();
            $i++;
        }
        extract($product);          //Zbog ovoga mogu koristiti $product1, $product2, itd.
        extract($variation);

    }

    public static function csvtoarray($filename='', $delimiter) {
        if(!file_exists($filename) || !is_readable($filename)) return false;
        $header = NULL;
        $data = [];

        if(($handle = fopen($filename, 'r')) == true) {
            while (($row = fgetcsv($handle, 10000, $delimiter)) == true)
            {
                if(!$header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }

}