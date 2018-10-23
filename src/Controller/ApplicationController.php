<?php

namespace App\Controller;

use App\Entity\Application;
use App\Form\ApplicationType;
use App\Repository\ApplicationRepository;
use App\Repository\InChargePersonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/application")
 */
class ApplicationController extends Controller
{
    /**
     * @Route("/", name="application_index", methods="GET")
     */
    public function index(ApplicationRepository $applicationRepository): Response
    {
        return $this->render('application/index.html.twig', ['applications' => $applicationRepository->findAll()]);
    }

    /**
     * @Route("/new", name="application_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $application = new Application();
        $form = $this->createForm(ApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($application);
            $em->flush();

            return $this->redirectToRoute('application_index');
        }

        return $this->render('application/new.html.twig', [
            'application' => $application,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="application_show", methods="GET")
     */
    public function show(Application $application): Response
    {
        return $this->render('application/show.html.twig', ['application' => $application]);
    }

    /**
     * @Route("/{id}/edit", name="application_edit", methods="GET|POST")
     */
    public function edit(Request $request, Application $application, InChargePersonRepository $inChargePersonRepository): Response
    {
        $inChargePeopleList = [];
        $inChargePeopleList['followers'] = [];
        $inChargePeopleList['otherOnes'] = [];

        $peopleApplication = [];
        foreach ($application->getInChargePeople() as $person) {
            $peopleApplication[] = $person->getId();
        }

        if ($request->isMethod('POST')) {
            $post = $request->request->all();

            $application->removeEachInChargePerson();
            if (isset($post['followers'])) {
                foreach ($post['followers'] as $followerId) {
                    $application->addInChargePerson($inChargePersonRepository->findOneBy(array('id' => $followerId)));
                }
            }

            $this->getDoctrine()
                ->getManager()
                ->flush();

            return $this->redirectToRoute('application_edit', ['id' => $application->getId()]);
        }


        $inChargePeople = $inChargePersonRepository->findAll();
        foreach ($inChargePeople as $inChargePerson) {
            if ($inChargePerson->getClient() === $application->getClient()) {
                if (in_array($inChargePerson->getId(), $peopleApplication)) {
                    array_push($inChargePeopleList['followers'], $inChargePerson);
                }
                else {
                    array_push($inChargePeopleList['otherOnes'], $inChargePerson);
                }
            }
        }


        return $this->render('application/edit.html.twig', [
            'application' => $application,
            'followersPeople' => $inChargePeopleList['followers'],
            'otherPeople' => $inChargePeopleList['otherOnes'],
        ]);
    }

    /**
     * @Route("/{id}", name="application_delete", methods="DELETE")
     */
    public function delete(Request $request, Application $application): Response
    {
        if ($this->isCsrfTokenValid('delete'.$application->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($application);
            $em->flush();
        }

        return $this->redirectToRoute('application_index');
    }
}
