<?php

namespace App\Controller;

use App\Form\EmailBoHValidatorSampleType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SampleController extends AbstractController
{
    /**
     * @Route("/sample", name="sample")
     */
    public function index(Request $request)
    {
        $form = $this->createForm(EmailBoHValidatorSampleType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', '正しいメールアドレスです。');
        }

        return $this->render('sample/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
