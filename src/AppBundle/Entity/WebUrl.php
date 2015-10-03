<?php
/**
 *
 */
namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class WebUrl
{

  /**
   * @Assert\NotBlank()
   */
  protected $url;



  public function getUrl()
  {
      return $this->url;
  }

  public function setUrl($url)
  {
      $this->url = $url;
  }

}
