<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use AppBundle\Entity\WebUrl;
use AppBundle\Model\Scanner;

class DefaultController extends Controller
{
    protected $logger;

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // Get Logger.
        $logger = $this->get('logger');

        $form = $this->createFormBuilder()
            ->add('submitFile', 'file', array('label' => 'File to Submit'))
            ->add('checkup', 'submit', array('label' => 'Scan'))
            ->getForm();

        // Check if we are posting stuff
        if ($request->getMethod('post') == 'POST') {
            // Bind request to the form
            $form->handleRequest($request);

            // If form is valid
            if ($form->isValid()) {
                // Get file
                $file = $form->get('submitFile');

                // Your csv file here when you hit submit button
                $filename = $file->getData();

                // Get buzz browser.
                $buzz = $this->container->get('buzz');
                // Set timeout for browser.
                $buzz->getClient()->setTimeout(15000);

                $scanner = new Scanner($logger, $buzz);
                $drupal_websites = $scanner->run($filename);

                // replace this example code with whatever you need
                return $this->render('default/result.html.twig', array(
                    'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..'),
                    'result' => $drupal_websites,
                ));
            }

         }

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..'),
            'form' => $form->createView(),
        ));
    }
}
