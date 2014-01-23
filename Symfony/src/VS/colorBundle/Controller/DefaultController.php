<?php

namespace VS\colorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('VScolorBundle:Default:index.html.twig', array('name' => $name));
    }
}
