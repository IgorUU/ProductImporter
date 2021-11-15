<?php

namespace Drupal\product_importer\Form;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;

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

        $form['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
    );
    return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $csv_file = $form_state->getValue('import_csv');

        // $id = \Drupal::entityTypeManager()->getStorage('file')
        // ->loadByProperties(['filename' => 'nesto.csv']);
        // dsm($id);

        $file = File::load(reset($csv_file));

        $file->setPermanent();          //Da bih ga sačuvao u bazi mora biti setovan na permanent.

        $file->save();

        $data = $this->csvtoarray($file->getFileUri(), ';');

        //-----------------         KREIRANJE PROIZVODA         ------------------------
        $i = 1;

        foreach($data as $item) {
            $nesto = file_get_contents($item['Image']);
            $slika =
             file_save_data($nesto, 'public://'.pathinfo($item['Image'])['basename'], FileSystemInterface::EXISTS_RENAME);

            //-----------        Loading term entity and creating it if it doesn't exist        -----------

            $term = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $item['Category']]);
            if(empty($term)) {
                $term = Term::create([
                    'vid' => 'categories',
                    'name' => $item['Category'],
                ]);
                    $term->save();
            } else {
                $term = reset($term);
            }

            $color = \Drupal::entityTypeManager()
            ->getStorage('commerce_product_attribute_value')
            ->loadByProperties(['name' => $item['Color']]);
            if(empty($color)) {
                $color = ProductAttributeValue::create([
                    'attribute' => 'color',
                    'name' => $item['Color']
                ]);
                $color->save();
            } else {
                $color = reset($color);
            }

            $size = \Drupal::entityTypeManager()
            ->getStorage('commerce_product_attribute_value')
            ->loadByProperties(['name' => $item['Size']]);
            if(empty($size)) {
                $size = ProductAttributeValue::create([
                    'attribute' => 'size',
                    'name' => $item['Size']
                ]);
                $size->save();
            } else {
                $size = reset($size);
            }

            $gender = \Drupal::entityTypeManager()
            ->getStorage('commerce_product_attribute_value')
            ->loadByProperties(['name' => $item['Gender']]);
            $gender = reset($gender);

            $product = Product::create([
                'uid' => $i,
                'type' => 'default',
                'title' => $item['Title'],
                'body' => $item['Body'],
                'field_categories' => $term
            ]);
            $variation = ProductVariation::create([
                'type' => 'default',
                'sku' => $item['SKU'],
                'price' => new Price($item['Price'], 'EUR'),
                'field_stock' => $item['Lager'],
                'field_image' => [
                    'target_id' => $slika->id(),
                    'alt' => 'Sample',
                    'title' => 'Sample file'
                ],
                'attribute_color' => $color,
                'attribute_gender' => $gender,
                'attribute_size' => $size
            ]);

            $variation->save();
            $product->addVariation($variation);
            $product->save();

            $i++;
        }
    }

    public static function csvtoarray($filename, $delimiter) {
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