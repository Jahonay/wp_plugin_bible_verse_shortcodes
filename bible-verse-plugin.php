<?php

/**
 * Plugin Name: bible-verse-plugin
 * Plugin URI: https://www.jahonay.github.io/
 * First mock up of a plugin which will create a table for the WEB bible
 * translation and then I will add some fun game from there
 * Version: 0.1
 * Author: John-Mackey
 * Author URI: https://www.johnmackeydesigns.com/
 **/

global $wpdb;
function debug_txt($info)
{
    function debug_inner($info)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'debug';
        $format = array('%s');
        $wpdb->insert(
            $table_name,
            array(
                'text' => $info
            ),
            $format

        );
    }


    register_activation_hook(__FILE__, 'debug_inner');
}

function wp_create_database_table()
{
    global $wpdb;


    $table_verse = $wpdb->prefix . 'bible_verses';

    $table_key = $wpdb->prefix . 'bible_key';

    $table_commands = $wpdb->prefix . 'bible_commands';


    $charset_collate = $wpdb->get_charset_collate();

    $sql = "DROP TABLE IF EXISTS $table_verse";
    $wpdb->query($sql);
    $sql = "DROP TABLE IF EXISTS $table_key";
    $wpdb->query($sql);
    $sql = "DROP TABLE IF EXISTS $table_commands";
    $wpdb->query($sql);

    $sql1 = "CREATE TABLE $table_verse (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        `bcv` INT(5)  NULL,
        `book` INT(5)  NULL,
        `chapter` INT(5)  NULL,
        `verse` INT(5)  NULL,
        `text` TEXT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql2 = "CREATE TABLE $table_key (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        `book` INT(5) NULL,
        `name` VARCHAR(45) NULL,
        `testament` VARCHAR(45) NULL,
        `genre` VARCHAR(45) NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql3 = "CREATE TABLE $table_commands (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        `book` INT(5) NULL,
        `chapter` INT(5) NULL,
        `verse` VARCHAR(45) NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
}
register_activation_hook(__FILE__, 'wp_create_database_table');

register_activation_hook(__FILE__, 'insert_table_data_bible');

function insert_table_data_bible()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'bible_verses';
    $table_name_key = $wpdb->prefix . 'bible_key';
    $table_name_commands = $wpdb->prefix . 'bible_commands';

    $json_verses = file_get_contents(plugin_dir_path(__FILE__) . 't_web.json');
    $data = json_decode($json_verses, true);
    $data = $data['resultset']['row'];

    $json_keys = file_get_contents(plugin_dir_path(__FILE__) . 'key_english.json');
    $data_keys = json_decode($json_keys, true);
    $data_keys = $data_keys['resultset']['keys'];



    $filename = plugin_dir_path(__FILE__) . 't_web.csv';
    $sql = "LOAD DATA INFILE '" . $filename . "'
    INTO TABLE $table_name
    FIELDS TERMINATED BY '|' 
    LINES TERMINATED BY '\n'
    (bcv, book, chapter, verse, text)
    SET ID = NULL;
    ";
    $wpdb->query($sql);

    $filename = plugin_dir_path(__FILE__) . 'key_english.csv';
    $sql = "LOAD DATA INFILE '" . $filename . "'
    INTO TABLE $table_name_key
    FIELDS TERMINATED BY ',' 
    LINES TERMINATED BY '\n'
    (book, name, testament, genre)
    SET id = NULL;
    ";
    $wpdb->query($sql);

    $filename = plugin_dir_path(__FILE__) . 'commands.csv';
    $sql = "LOAD DATA INFILE '" . $filename . "'
    INTO TABLE $table_name_commands
    FIELDS TERMINATED BY ',' 
    LINES TERMINATED BY '\n'
    (book, chapter, verse)
    SET id = NULL;
    ";
    $wpdb->query($sql);
}

function random_bible_command()
{
    $row = rand(1, 613);

    global $wpdb;

    $verse_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_bible_commands c LEFT JOIN wp_bible_key k on c.book=k.book LEFT JOIN wp_bible_verses v ON v.book=c.book AND v.chapter=c.chapter AND v.verse=c.verse WHERE c.id = %d", $row));



    return '<b>' . $verse_row->name . ' ' . $verse_row->chapter . ':' . $verse_row->verse . ' </b>' .  stripslashes($verse_row->text);
}

add_shortcode('random_bible_command', 'random_bible_command');
function select_bible_verse($atts = array())
{
    global $wpdb;

    extract(shortcode_atts(array(
        'book' => 1,
        'chapter' => 2,
        'verse' => 3,
    ), $atts));

    $verse_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_bible_verses v LEFT JOIN wp_bible_key k ON v.book=k.book WHERE k.name=%s AND chapter=%d AND verse = %d", array($book, $chapter, $verse)));

    return '<b>' . $verse_row->name . ' ' . $verse_row->chapter . ':' . $verse_row->verse . ' </b>' .  stripslashes($verse_row->text);
}
add_shortcode('select_bible_verse', 'select_bible_verse');
