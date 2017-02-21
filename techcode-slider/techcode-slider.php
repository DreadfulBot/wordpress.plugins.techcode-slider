<?php
/**
 * @package techcode-slider
 * @version 1.0
 * Date: 20.02.2017
 * Time: 11:00
 */

/*
 * Plugin Name: Система встраиваемых слайдеров techcode
 * Plugin URI: www.kondraland.ru
 * Description: Плагин для встраиваемых слайдеров
 * Armstrong: coding is art
 * Author: Krivoshchekov Artem
 * Version: 1.0
 * Author URI: www.kondraland.ru
 */


function techcode_install()
{
    global $wpdb;

    $table_sliders = $wpdb->prefix .techcode_sliders;
    $table_slides = $wpdb->prefix . techcode_slides;
    $table_slider_slide = $wpdb->prefix . techcode_slider_slide;

    $sql1 = "
        CREATE TABLE IF NOT EXISTS `" . $table_sliders . "` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `shortcode_name` VARCHAR(255) NOT NULL,
          `slider_class` VARCHAR(255) NOT NULL,
          `slide_class` VARCHAR(255) NOT NULL,
          `slider_id` VARCHAR(255) NOT NULL,
          `caption_class` VARCHAR(255) NOT NULL,
          `slider_description` VARCHAR(255) NOT NULL,
          PRIMARY KEY (`id`))
        ENGINE = InnoDB
    ";

    $sql2 = "
        CREATE TABLE IF NOT EXISTS `" . $table_slides . "` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `slide_id` VARCHAR(255) NOT NULL,
            `slide_caption` VARCHAR(255) NOT NULL,
            `image_url` VARCHAR(255) NOT NULL,
            `link_url` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`))
        ENGINE = InnoDB
    ";

    $sql3 = "
        CREATE TABLE IF NOT EXISTS `" . $table_slider_slide . "` (
          `slider_id` INT NOT NULL,
          `slide_id` INT NOT NULL,
          `order_priority` INT NOT NULL,
          PRIMARY KEY (`slider_id`, `slide_id`),
          INDEX `SLIDES_idx` (`slide_id` ASC),
          CONSTRAINT `SLIDERS`
            FOREIGN KEY (`slider_id`)
            REFERENCES `" . $table_sliders . "` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
          CONSTRAINT `SLIDES`
            FOREIGN KEY (`slide_id`)
            REFERENCES `" . $table_slides . "` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE)
        ENGINE = InnoDB
    ";

    $wpdb->query($sql1);
    $wpdb->query($sql2);
    $wpdb->query($sql3);
}

function techcode_uninstall()
{
    global $wpdb;

    $table_sliders = $wpdb->prefix . techcode_sliders;
    $table_slides = $wpdb->prefix . techcode_slides;
    $table_slider_slide = $wpdb->prefix . techcode_slider_slide;

    $sql1 = "DROP TABLE `" . $table_slider_slide . "`;";
    $sql2 = "DROP TABLE `" . $table_sliders . "`;";
    $sql3 = "DROP TABLE `" . $table_slides . "`;";

    $wpdb->query($sql1);
    $wpdb->query($sql2);
    $wpdb->query($sql3);

}

function techcode_slider_add_admin_pages()
{
    add_options_page('Настройки слайдеров', 'Настройки слайдеров', 8, 'techcode-slider', 'techcode_slider_options_page');
}


function techcode_slider_options_page()
{
    echo '<h2>Настройка системы слайдеров techcode</h2>';
    echo '<p>Автор плагина: <a href="https://vk.com/justahuman">Кривощеков Артем</a>';

    // добавить слайдер
    echo '<h3>Добавить слайдер</h3>';
    techcode_add_slider();

    // отредактировать существующие
    echo '<h3>Добавленные слайдеры</h3>';
    techcode_change_slider();
}

function insert_slides($slider_id, $slides) {
    global $wpdb;
    $table_slides = $wpdb->prefix . techcode_slides;
    $table_slider_slide = $wpdb->prefix . techcode_slider_slide;

    // обрабатываем каждую строчку из области слайдов
    $slider_lines = explode(PHP_EOL, $slides);

    $index = 0;
    foreach ($slider_lines as $slider_line) {
        // отделяем картинку от описания
        $slider_exploded = explode(';', $slider_line);

//        echo "<strong>Изображение</strong> - " . $slider_exploded[0] . ' <strong>описание</strong> - ' . $slider_exploded[1] . '<br/>';

        // добавляем слайд
        $wpdb->insert(
            $table_slides,
            array(
                'image_url' => $slider_exploded[0],
                'slide_caption' => $slider_exploded[1],
                'link_url' => $slider_exploded[2]
            ),
            array(
                '%s', '%s', '%s'
            )
        );

        // добавляем слайд к слайдеру
        $slide_id = $wpdb->insert_id;

        $wpdb->insert(
            $table_slider_slide,
            array(
                'slider_id' => $slider_id,
                'slide_id' => $slide_id,
                'order_priority' => $index
            ),
            array(
                '%d', '%d', '%d'
            )
        );
        $index = $index + 1;
    }
}

function techcode_add_slider()
{
    global $wpdb;

    $table_sliders = $wpdb->prefix . techcode_sliders;


    if (isset($_POST['techcode_add_slider_btn'])) {

        if (function_exists('current_user_can') &&
            !current_user_can('manage_options')
        )
            die(_e('HACKER?', 'techcode'));

        if (function_exists('check_admin_referer'))
            check_admin_referer('techcode_add_slider');

        $shortcode_name = $_POST['techcode_shortcode_name'];
        $slider_class = $_POST['techcode_slider_class'];
        $slider_id = $_POST['techcode_slider_id'];
        $slide_class = $_POST['techcode_slide_class'];
        $caption_class = $_POST['techcode_caption_class'];
        $slides = $_POST['techcode_slides'];
        $slider_description = $_POST['techcode_slider_description'];

        // создаем новый слайдер
        $wpdb->insert(
            $table_sliders,
            array(
                'shortcode_name' => $shortcode_name,
                'slider_class' => $slider_class,
                'slide_class' => $slide_class,
                'slider_id' => $slider_id,
                'caption_class' => $caption_class,
                'slider_description' => $slider_description
            ),
            array(
                '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );

        // получаем id добавленного слайдера
        $slider_db_id = $wpdb->insert_id;

        // добавляем слайды
        insert_slides($slider_db_id, $slides);
    }

    echo '
        <form name="techcode_add_slider" method="post" action="' . $_SERVER['PHP_SELF'] . '?page=techcode-slider&amp;created=true">
        ';

    if (function_exists('wp_nonce_field'))
        wp_nonce_field('techcode_add_slider');

    echo '
    <table>
        <tr>
            <td style="text-align: right">Описание слайдера</td>
            <td><input type="text" name="techcode_slider_description"></td>
        </tr>
        <tr>
            <td style="text-align: right">Имя шорткода</td>
            <td><input type="text" name="techcode_shortcode_name"></td>
        </tr>
        <tr>
            <td style="text-align: right">Класс слайдера</td>
            <td><input type="text" name="techcode_slider_class"></td>
        </tr>
        <tr>
            <td style="text-align: right">Идентификатор слайдера</td>
            <td><input type="text" name="techcode_slider_id"></td>
        </tr>
        <tr>
            <td style="text-align: right">Класс слайда</td>
            <td><input type="text" name="techcode_slide_class"></td>
        </tr>
        <tr>
            <td style="text-align: right">Класс описания</td>
            <td><input type="text" name="techcode_caption_class"></td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center">Слайды с описаниями и ссылками (по 1 в каждой строке через знак ";")<br/>
               <b>Формат:</b> [ссылка_на_картинку];[описание_картинки];[ссылка_на_сайт]<br/>
                <b>Например:</b><br/>
                    <i><span style="color: green">www.site.ru/image.png;картинка с www.site.ru;www.site.ru<br>
                    <i><span style="color: green">www.site.ru/image2.png;картинка 2 с www.site.ru;www.site.ru<br>
                </span></i>
            </td>
        </tr>
        <tr>
             <td colspan="2"><textarea style="width: 100%; height:200px" name="techcode_slides"></textarea></td>  
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>
                <input type="submit" name="techcode_add_slider_btn" value="Добавить слайдер" style="width: 140px"/>
            </td>
        </tr>
    </table>';

    echo '</form>';
}

function techcode_change_slider()
{
    global $wpdb;

    $table_sliders = $wpdb->prefix . techcode_sliders;
    $table_slides = $wpdb->prefix . techcode_slides;
    $table_slider_slide = $wpdb->prefix . techcode_slider_slide;

    // нажата клавиша "изменить слайдер"
    if (isset($_POST['techcode_change_slider_btn'])) {

        if (function_exists('current_user_can') &&
            !current_user_can('manage_options')
        )
            die(_e('HACKER?', 'techcode'));

        if (function_exists('check_admin_referer'))
            check_admin_referer('techcode_add_slider');

        $shortcode_name = $_POST['techcode_shortcode_name'];
        $slider_description = $_POST['techcode_slider_description'];
        $slider_class = $_POST['techcode_slider_class'];
        $slider_id = $_POST['techcode_slider_id'];
        $slide_class = $_POST['techcode_slide_class'];
        $caption_class = $_POST['techcode_caption_class'];
        $slides = $_POST['techcode_slides'];
        $slider_db_id = $_POST['techcode_slider_db_id'];

        // изменяем выбранный слайдер
        $wpdb->update(
            $table_sliders,
            array(
                'slider_description' => $slider_description,
                'shortcode_name' => $shortcode_name,
                'slider_class' => $slider_class,
                'slide_class' => $slide_class,
                'slider_id' => $slider_id,
                'caption_class' => $caption_class
            ),
            array(
                'id' => $slider_db_id
            ),
            array(
                '%s', '%s', '%s', '%s', '%s'
            ),
            array(
                '%d'
            )
        );

        // получаем все слайды слайдера
        $slide_ids = $wpdb->get_results('SELECT `slide_id` FROM `' . $table_slider_slide . '` WHERE `slider_id` = ' . $slider_db_id);

        // удаляем все слайды слайдера
        foreach ($slide_ids as $slide_id) {
            $wpdb->delete($table_slides, array('id' => $slide_id->slide_id));
        }

        // обновляем слайды
        insert_slides($slider_db_id, $slides);
    }

    // нажата клавиша "удалить плагин"
    if(isset($_POST['techcode_delete_slider_btn'])) {
        if (function_exists('current_user_can') &&
            !current_user_can('manage_options')
        )
            die(_e('HACKER?', 'techcode'));

        if (function_exists('check_admin_referer'))
            check_admin_referer('techcode_add_slider');

        $slider_db_id = $_POST['techcode_slider_db_id'];

        // получаем все слайды слайдера
        $slide_ids = $wpdb->get_results('SELECT `slide_id` FROM `' . $table_slider_slide . '` WHERE `slider_id` = ' . $slider_db_id);

        // удаляем все слайды слайдера
        foreach ($slide_ids as $slide_id) {
            $wpdb->delete($table_slides, array('id' => $slide_id->slide_id));
        }

        // удаляем связи
        $wpdb->delete($table_slider_slide, array('slider_id' => $slider_db_id));

        // удаляем слайдер
        $wpdb->delete($table_sliders, array('id' => $slider_db_id));
    }


    // по всем слайдерам
    $sliders = $wpdb->get_results('SELECT * FROM `' . $table_sliders . '`');

    foreach ($sliders as $slider) {
        $slides_concat = "";

        // по всем слайдам слайдера (через связующую таблицу)
        $slider_slides = $wpdb->get_results('
          SELECT * FROM `' . $table_slider_slide . '` AS `TSS`
            INNER JOIN `' . $table_slides . '` AS `TS` ON (`TSS`.`slide_id` = `TS`.`id`)
            WHERE `TSS`.`slider_id` =' . $slider->id . ';'
        );

        // конкатинируем информацию со всех слайдов в одну строчку
        foreach ($slider_slides as $slide) {
            $slides_concat .= $slide->image_url . ';' . $slide->slide_caption . ';' . $slide->link_url . PHP_EOL;
        }

        echo '
        <form name="techcode_add_slider" method="post" action="' . $_SERVER['PHP_SELF'] . '?page=techcode-slider&amp;created=true">
        ';

        if (function_exists('wp_nonce_field'))
            wp_nonce_field('techcode_add_slider');

        echo '
        <table>
            <tr>
                <td style="text-align: right">Описание слайдера</td>
                <td><input type="text" name="techcode_slider_description" value="'.$slider->slider_description.'"></td>
            </tr>
            <tr>
                <td style="text-align: right">Имя шорткода</td>
                <td><input type="text" name="techcode_shortcode_name" value="' . $slider->shortcode_name . '"></td>
            </tr>
            <tr>
                <td style="text-align: right">Класс слайдера</td>
                <td><input type="text" name="techcode_slider_class" value="' . $slider->slider_class . '"></td>
            </tr>
            <tr>
                <td style="text-align: right">Идентификатор слайдера</td>
                <td><input type="text" name="techcode_slider_id" value="' . $slider->slider_id . '"></td>
            </tr>
            <tr>
                <td style="text-align: right">Класс слайда</td>
                <td><input type="text" name="techcode_slide_class" value="' . $slider->slide_class . '"></td>
            </tr>
            <tr>
                <td style="text-align: right">Класс описания</td>
                <td><input type="text" name="techcode_caption_class" value="' . $slider->caption_class . '"></td>
            </tr>
            <tr>
               <td colspan="2" style="text-align: center">Слайды с описаниями и ссылками (по 1 в каждой строке через знак ";")<br/>
               <b>Формат:</b> [ссылка_на_картинку];[описание_картинки];[ссылка_на_сайт]<br/>
                <b>Например:</b><br/>
                    <i><span style="color: green">www.site.ru/image.png;картинка с www.site.ru;www.site.ru<br>
                    <i><span style="color: green">www.site.ru/image2.png;картинка 2 с www.site.ru;www.site.ru<br>
                </span></i>
            </td>
            </tr>
            <tr>
                 <td colspan="2"><textarea style="width: 100%; height:200px;" name="techcode_slides">' . $slides_concat . '</textarea></td>
            </tr>
            <tr>
                <td><input type="hidden" name="techcode_slider_db_id" value="' . $slider->id . '"</td>
                <td>
                    <input type="submit" name="techcode_change_slider_btn" value="Изменить слайдер" style="width: 140px"/>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <input type="submit" name="techcode_delete_slider_btn" value="Удалить слайдер" style="width: 140px"/>
                </td>
            </tr>
        </table><hr/>
        ';

        echo '</form>';
    }
}

function techcode_slider_run() {

}

// функция отображения контента шорткода
// $params->name - ожидаемое имя шорткода
function techcode_display_slider($params) {
    global $wpdb;

    $table_sliders = $wpdb->prefix . techcode_sliders;
    $table_slides = $wpdb->prefix . techcode_slides;
    $table_slider_slide = $wpdb->prefix . techcode_slider_slide;

    $slider = $wpdb->get_results(
        '
            SELECT SLIDERS.*, SLIDES.* FROM `'.$table_sliders.'` AS SLIDERS
            INNER JOIN `'.$table_slider_slide.'` AS TSS ON (SLIDERS.id = TSS.slider_id)
            INNER JOIN `'.$table_slides.'` AS SLIDES ON (TSS.slide_id = SLIDES.id)
            WHERE `SLIDERS`.`shortcode_name` = "'.$params['name'].'"
      ');


    $slider_class = $slider[0]->slider_class;
    $slider_id = $slider[0]->slider_id;
    $slide_class = $slider[0]->slide_class;
    $caption_class = $slider[0]->caption_class;

    echo '<div id='.$slider_id.' class="'.$slider_class.'">';

    foreach ($slider as $slider_image) {
        echo '<div>';

        if(!empty($slider_image->link_url))
            echo '<a href="'.$slider_image->link_url.'">';

        echo '<img class="'.$slide_class.'" src="'.$slider_image->image_url.'">';

        if(!empty($slider_image->link_url))
            echo '</a>';

        if(!empty($slider_image->slide_caption))
            echo '<p class="'.$caption_class.'">'.$slider_image->slide_caption.'</p>';

        echo '</div>';
    }

    echo '</div>';
}

add_shortcode('techcode-slider', 'techcode_display_slider');

register_activation_hook(__FILE__, 'techcode_install');
register_deactivation_hook(__FILE__, 'techcode_uninstall');

add_action('admin_menu', 'techcode_slider_add_admin_pages');
add_action('init', 'techcode_slider_run');

