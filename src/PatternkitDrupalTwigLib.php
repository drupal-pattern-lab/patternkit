<?php

/**
 * Class PatternkitDrupalTwigLib.
 */
class PatternkitDrupalTwigLib extends PatternkitDrupalCachedLib {

  private $id;

  private $path;

  private $title;

  /**
   * PatternkitDrupalTwigLib constructor.
   *
   * @param string $title
   *   The name of the library.
   * @param string $path
   *   The path to the library.
   */
  public function __construct($title, $path) {
    $this->title = $title;
    $this->id = preg_replace('/[^a-z0-9]+/', '_', strtolower($title));
    $this->path = $path;
  }

  /**
   * Returns the id of the Pattern Library.
   *
   * @return string
   *   The Pattern Library id.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Returns the title of the Pattern Library.
   *
   * @return string
   *   The Pattern Library title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Returns renderable data or markup for a pattern editor.
   *
   * @param string|null $subtype
   *   If specified, return an editor customized for this subtype.
   * @param \PatternkitEditorConfig $config
   *   Optional configuration settings for the editor.
   *
   * @return mixed
   *   The renderable pattern editor.
   */
  public function getEditor($subtype = NULL,
    PatternkitEditorConfig $config = NULL) {
    if (empty($subtype) || empty($metadata)) {
      return array(

      )
    }
    $metadata = $this->getMetadata($subtype);
    return array();
  }

  /**
   * Utility function to get all Patternkit module metadata.
   *
   * @return array
   *   Array of metadata objects found.
   */
  protected function getRawMetadata() {
    $it = new RecursiveDirectoryIterator($this->path);
    $filter = array('json');
    $metadata = array();
    /** @var \SplFileInfo $file */
    foreach (new RecursiveIteratorIterator($it) as $file) {
      if (!$file->isFile()) {
        continue;
      }
      $file_path = $file->getPath();
      $dirs = explode(DIRECTORY_SEPARATOR, $file_path);
      // All JSON schema must be in an 'api' folder at this time.
      // @todo Add support for custom setups.
      $num_dirs = count($dirs);
      if ($num_dirs < 2
        || array_pop($dirs) !== 'api') {
        continue;
      }
      $file_ext = $file->getExtension();
      if (!in_array(strtolower($file_ext), $filter, TRUE)) {
        continue;
      }
      if ($file_contents = file_get_contents($file)) {
        $schema = json_decode($file_contents);
        $schema->html = NULL;
        $schema->library = &$this;
        $schema->version = isset($schema->version) ? $schema->version : 0;
        $file_basename = $file->getBasename('.json');
        $twig_file = $file_path
          . DIRECTORY_SEPARATOR . $file_basename . '.twig';
        if (file_exists($twig_file)) {
          $schema->twig = file_get_contents($twig_file);
        }
        $metadata[$file_basename] = $schema;
      }
      if (count($metadata) > 500) {
        break;
      }
    }
    return $metadata;
  }

}
