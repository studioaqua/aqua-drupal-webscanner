<?php
/**
 *
 */
namespace AppBundle\Model;

//use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Buzz\Browser;

define('IS_DRUPAL_URL', 'http://isthissitebuiltwithdrupal.com/');

class Scanner
{
  // Logger
  private $logger;

  // Browser
  private $browser;

  // Google search url
  private $google_url = 'https://ajax.googleapis.com/ajax/services/search/web?v=1.0&q=site:';

  // Regular expression
  //private $rexp_company_page = '/(<h2><a href=")([^\"]*)/';
  //private $rexp_company_name = '/(<h2><strong>)([^<]*)/';
  //private $rexp_company_website = "/(<a href=['])([^']*)(' class='myrp_visit_link)/";

  private $rexp_answer = '/(class="answer">)([^<]*)/';

  public function __construct(LoggerInterface $logger, Browser $browser)
  {
    // Init Logger
    $this->logger = $logger;

    // Init browser
    $this->browser = $browser;

    $this->logger->debug('Init Scanner');
  }


  private function is_drupal($href)
  {
    $response = $this->browser->get(IS_DRUPAL_URL . $href);
    if ($response->getStatusCode() == 200)
    {
        preg_match_all($this->rexp_answer, $response->getContent(), $match);
        $this->logger->debug(
          sprintf('[is_drupal] Is drupal? %s',  var_export($match, TRUE))
        );

        if (isset($match[2][0])
            && $match[2][0] == 'yes')
        {
            return true;
        }
        else
        {
            return false;
        }
    }
  }


  /**
   *
   */
  public function run($csv_filename)
  {

    $this->logger->info(sprintf(
      'Scanning %s ...',
      $csv_filename
    ));

    $row = 1;
    $output = array();
    if (($handle = fopen($csv_filename, "r")) !== FALSE)
    {
      while (($data = fgetcsv($handle, 100, ",")) !== FALSE)
      {
        $num = count($data);
        $this->logger->debug(sprintf(
          'line %s',
          $row
        ));
        $row++;

        // Jump
        if ($row > 1)
        {
          $this->logger->debug($data[0]);
          if ($this->is_drupal($data[0]))
          {
            $output[] = array(
              'url' => $data[0],
            );
          }

        }
      }
      fclose($handle);
    }

    return $output;
  }


  private function strposa($haystack, $needle, $offset=0)
  {
    if(!is_array($needle))
    {
      $needle = array($needle);
    }

    foreach($needle as $query)
    {
      if(strpos($haystack, $query, $offset) !== false)
      {
        // stop on first true result
        return true;
      }
    }
    return false;
  }

}

?>

