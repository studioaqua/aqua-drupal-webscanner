<?php

namespace AppBundle\Model;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Buzz\Browser;
//use Monolog\Processor\PsrLogMessageProcessor;

define('IS_DRUPAL_URL', 'http://isthissitebuiltwithdrupal.com/');

class VotaziendaScanner
{
  // Logger
  private $logger;

  // Browser
  private $browser;

  // Regular expression
  private $rexp_company_page = '/(<h2><a href=")([^\"]*)/';
  private $rexp_company_name = '/(<h2><strong>)([^<]*)/';
  private $rexp_company_website = "/(<a href=['])([^']*)(' class='myrp_visit_link)/";

  private $rexp_answer = '/(class="answer">)([^<]*)/';

  private $companies_with_drupal = array();

  public function __construct(LoggerInterface $logger, Browser $browser)
  {
    // Init Logger
    $this->logger = $logger;

    // Init browser
    $this->browser = $browser;

    // Init lock filename
    //$this->lock_filename = dirname(__FILE__) . '/.lock';
    //$this->lock_filename_respo = dirname(__FILE__) . '/.lock.respo';

    $this->logger->debug('Init VotaziendaScanner');
  }

  /**
   * This function find the list of company from Votazienda.it pages.
   *
   * @param  string  $html_source
   *         the html source code
   * @return array
   *         returns the list of company name and website url.
   */
  private function loadCompanies($html_source)
  {

    preg_match_all($this->rexp_company_page, $html_source, $match);

    if (!empty($match[2]))
    {
        foreach ($match[2] as $href)
        {
          if (!empty($href)) {
            $this->logger->debug('[loadCompanies] Company page: ' . $href);

            $response = $this->browser->get($href);
            if ($response->getStatusCode() == 200)
            {
                // Retrieves company name
                preg_match_all($this->rexp_company_name, $response->getContent(), $match_name);
                $this->logger->debug('[loadCompanies] Company name: ' . $match_name[2][0]);

                // Retrieve company website url
                preg_match_all($this->rexp_company_website, $response->getContent(), $match_url);
                $this->logger->debug('[loadCompanies] Company url: ' . $match_url[2][0]);

                if (isset($match_url[2][0])
                    && $this->is_drupal($match_url[2][0]))
                {
                    $this->companies_with_drupal[] = array(
                        'name'  => $match_name[2][0],
                        'url'   => $match_url[2][0],
                    );

                    $this->logger->info('[loadCompanies] The website "'
                        . $match_url[2][0] . '" is built in Drupal!');
                }

            }
          }

        }
    }

  }


  private function is_drupal($href)
  {
    $response = $this->browser->get(IS_DRUPAL_URL . $href);
    if ($response->getStatusCode() == 200)
    {
        preg_match_all($this->rexp_answer, $response->getContent(), $match);
        $this->logger->debug('[loadCompanies] Is drupal? ' . $match[2][0]);

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
  public function run($source_href, $page_querystring)
  {
        // Init response code OK, and page number.
        $response_code = 200;
        $page_number = 1;


        //
        while ($response_code == 200)
        {
            $href = $source_href
                . sprintf($page_querystring, $page_number);

            $this->logger->info(sprintf(
              'Scanning %s ...',
              $href
            ));

            $response = $this->browser->get($href);
            $response_code = $response->getStatusCode();

            $this->loadCompanies($response->getContent());

            $page_number++;
        }

        return $this->companies_with_drupal;
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
