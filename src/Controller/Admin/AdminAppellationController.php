<?php

namespace App\Controller\Admin;

use App\Entity\Appellation;
use App\Entity\Country;
use App\Entity\CountryRegion;
use App\Filter\AppellationFilter;
use App\Form\AppellationType;
use App\Repository\AppellationRepository;
use App\Repository\CountryRegionRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminAppellationController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10000;
    CONST MODEL = 'appellation';
    CONST ENTITY_NAME = 'Appellation';
    CONST NS_ENTITY_NAME = 'App:Appellation';

    /**
     * Lists all appellation entities.
     *
     * @Route("backend/appellation/index", name="backend_appellation_index", methods={"GET","HEAD"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, AppellationFilter::class,
            'name', 'ASC');


        return $this->render('admin/common/index.html.twig', array(
            'pagination' => $pagination,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'appellation.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'appellation.name',
                    'sortable' => true,
                ],
                'a.country' => [
                    'title' => 'Country',
                    'row_field' => 'country',
                    'sorting_field' => 'appellation.country',
                    'sortable' => false,
                ],
            ]
        ));
    }

    /**
     * Creates a new appellation entity.
     *
     * @Route("backend/appellation/new", name="backend_appellation_new", methods={"GET","POST"})
     */
    public function newAction(Request $request, ValidatorInterface $validator)
    {
        $appellation = new Appellation();
        $form = $this->createForm(AppellationType::class, $appellation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($appellation);
            $this->em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_appellation_edit', array('id' => $appellation->getId()));
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'appellation' => $appellation,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,

        ));
    }

    /**
     * Finds and displays a appellation entity.
     *
     * @Route("backend/appellation/{id}", name="backend_appellation_show", methods={"GET"})
     */
    public function showAction(Appellation $appellation)
    {
        $deleteForm = $this->createDeleteForm($appellation);

        return $this->render('admin/appellation/show.html.twig', array(
            'appellation' => $appellation,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * @Route("backend/appellation/default/wp", name="backend_appellation_wp", methods={"GET"})
     */
    public function setDefaultWP(EntityManagerInterface $entityManager)
    {
        $queryBuilder = $entityManager->createQueryBuilder();
        $query = $queryBuilder
            ->update('App:Appellation', 'c')
            ->set('c.worldPart', ':wp')
            ->setParameter('wp', 'old_world')
            ->getQuery();

        $query ->execute();

        return new Response('done');
    }

    /**
     * Displays a form to edit an existing appellation entity.
     *
     * @Route("backend/appellation/{id}/edit", name="backend_appellation_edit", methods={"GET","POST"})
     */
    public function editAction(Request $request, Appellation $appellation, FileUploader $fileUploader)
    {
        $editForm = $this->createForm(AppellationType::class, $appellation);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $appellation->getFlagPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $appellation->setFlagPic($fileName);
            }

            $this->em->persist($appellation);
            $this->em->flush();

            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_appellation_edit', array('id' => $appellation->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        $deleteForm = $this->createDeleteForm($appellation);

        return $this->render('admin/appellation/edit.html.twig', array(
            'row' => $appellation,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Displays a form to edit an existing appellation entity.
     *
     * @Route("backend/appellation/import/country/{codeAlpha2}", name="backend_appellation_import", methods={"GET","POST"})
     */
    public function import(Country $country,
                           CountryRegionRepository $countryRegionRepository,
                           AppellationRepository $appellationRepository,
                           Request $request)
    {
        $path = $this->getParameter('uploads_directory').'/fr.csv';
        if (!file_exists($path)) {
            throw new \Exception($path.' not exist');
        }
        $handle = fopen($path, "r"); // open in readonly mode
        while (($row = fgetcsv($handle)) !== false) {
            $regionName = $row[0];
            $appellacionName = $row[1];

            $region = $countryRegionRepository->findOneBy([
                'name' => $regionName,
                'country' => $country,
            ]);

            if (!$region) {
                $region = (new CountryRegion())
                    ->setName($regionName)
                    ->setCountry($country);
                $this->em->persist($region);
            }
            /** @var Appellation $appellacion */
            $appellacion = $appellationRepository->findOneBy([
                'name' => $appellacionName,
                'country' => $country,
            ]);

            if (!$appellacion) {
                $appellacion = (new Appellation())
                    ->setCountry($country)
                    ->setCountryRegion($region)
                    ->setName($appellacionName);
                $this->em->persist($appellacion);
            }
        }

        fclose($handle);
        $this->em->flush();

        return $this->redirectToRoute('backend_appellation_index');
    }

    /**
     * Deletes a appellation entity.
     *
     * @Route("backend/appellation/{id}", name="backend_appellation_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Appellation $appellation, FileUploader $fileUploader)
    {
        $filter_form = $this->createDeleteForm($appellation);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $appellation->getFlagPicFile();

            if (null !== $file) {
                $fileName = $fileUploader->upload($file);
                $appellation->setFlagPic($fileName);
            }


            $this->em->remove($appellation);
            $this->em->flush();

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_appellation_index');
    }

    /**
     * Creates a form to delete a appellation entity.
     */
    private function createDeleteForm(Appellation $appellation) : FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_appellation_delete', array('id' => $appellation->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

}
