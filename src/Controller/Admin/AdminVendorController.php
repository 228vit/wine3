<?php

namespace App\Controller\Admin;

use App\Entity\Vendor;
use App\Entity\VendorPic;
use App\Filter\VendorFilter;
use App\Form\VendorType;
use App\Repository\VendorRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Knp\Component\Pager\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class AdminVendorController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'vendor';
    CONST ENTITY_NAME = 'Vendor';
    CONST NS_ENTITY_NAME = 'App:Vendor';

    /**
     * Lists all vendor entities.
     *
     * @Route("backend/vendor/index", name="backend_vendor_index", methods={"GET"})
     */
    public function indexAction(Request $request, SessionInterface $session)
    {
        $pagination = $this->getPagination($request, $session, VendorFilter::class);

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
                    'sorting_field' => 'vendor.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'vendor.name',
                    'sortable' => true,
                ],
                'a.slug' => [
                    'title' => 'Slug',
                    'row_field' => 'slug',
                    'sorting_field' => 'vendor.slug',
                    'sortable' => false,
                ],
            ]
        ));
    }


    /**
     * Creates a new vendor entity.
     *
     * @Route("backend/vendor/new", name="backend_vendor_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, VendorRepository $repository, EntityManagerInterface $em)
    {
        $vendor = new Vendor();
        $form = $this->createForm('App\Form\VendorType', $vendor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $vendor->setSlug($this->makeSlug($vendor, $repository));

            $em->persist($vendor);
            $em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_vendor_edit', array('id' => $vendor->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'row' => $vendor,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    private function makeSlug(Vendor $vendor, VendorRepository $repository)
    {
        $slug = $vendor->getSlug() ?? Slugger::urlSlug($vendor->getName(), array('transliterate' => true));

        while($repository->slugExists($slug)) {
            $slug .= '-' . rand(1000, 9999);
        }

        return $slug;
    }

    /**
     * Finds and displays a vendor entity.
     *
     * @Route("backend/vendor/{id}", name="backend_vendor_show", methods={"GET"})
     */
    public function showAction(Vendor $vendor)
    {
        $deleteForm = $this->createDeleteForm($vendor);

        return $this->render('admin/vendor/show.html.twig', array(
            'vendor' => $vendor,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing vendor entity.
     *
     * @Route("backend/vendor/{id}/edit", name="backend_vendor_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Vendor $vendor, FileUploader $fileUploader, EntityManagerInterface $em)
    {
        $deleteForm = $this->createDeleteForm($vendor);
        $form = $this->createForm(VendorType::class, $vendor);
        $form->handleRequest($request);
        $originalImages = new ArrayCollection();

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $vendor->getLogoFile();

            if (null !== $file) {
                $fileName = $fileUploader->uploadVendorLogo($file, $vendor);
                $vendor->setLogo($fileName);
            }

            foreach ($originalImages as $image) {
                if (false === $vendor->getPics()->contains($image)) {
                    // remove the Task from the Tag
                    $vendor->getPics()->removeElement($image);
                    // todo: check if image deleted
                    $em->persist($vendor);
                    $em->remove($image);
                }
            }

            // save and create new images
            $images = $form['pics']->getData();

            /** @var VendorPic $image */
            foreach ($images as $i => $image) {
                $picFile = $form['pics'][$i]['picFile']->getData();
                if ($picFile) {
                    $fileName = $fileUploader->uploadVendorPic($picFile, $vendor);
                    $image->setPosition($i);
                    $image->setPic($fileName);
                    $image->setVendor($vendor);
                    $originalImages->add($image);
                }
            }

            $this->em->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_vendor_edit', array('id' => $vendor->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        return $this->render('admin/vendor/edit.html.twig', array(
            'row' => $vendor,
            'form' => $form->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a vendor entity.
     *
     * @Route("backend/vendor/{id}", name="backend_vendor_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Vendor $vendor)
    {
        $filter_form = $this->createDeleteForm($vendor);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($vendor);
            $em->flush();

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_vendor_index');
    }

    /**
     * Creates a form to delete a vendor entity.
     *
     * @param Vendor $vendor The vendor entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Vendor $vendor)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_vendor_delete', array('id' => $vendor->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
