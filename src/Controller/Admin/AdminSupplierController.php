<?php

namespace App\Controller\Admin;

use App\Entity\Offer;
use App\Entity\Supplier;
use App\Filter\SupplierFilter;
use App\Form\SupplierType;
use App\Repository\SupplierRepository;
use App\Service\FileUploader;
use App\Utils\Slugger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Knp\Component\Pager\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class AdminSupplierController extends AbstractController
{
    use AdminTraitController;

    CONST ROWS_PER_PAGE = 10;
    CONST MODEL = 'supplier';
    CONST ENTITY_NAME = 'Supplier';
    CONST NS_ENTITY_NAME = 'App:Supplier';

    /**
     * Lists all supplier entities.
     *
     * @Route("backend/supplier/index", name="backend_supplier_index", methods={"GET"})
     */
    public function indexAction(Request $request,
                                SupplierRepository $supplierRepository,
                                SessionInterface $session)
    {
//        $pagination = $this->getPagination($request, $session, SupplierFilter::class);

        $this->filter_form = $this->createForm(SupplierFilter::class, null, array(
            'action' => $this->generateUrl('backend_apply_filter', ['model' => self::MODEL]),
            'method' => 'POST',
        ));

        $rows = $supplierRepository->withCountOffers();

        return $this->render('admin/supplier/index.html.twig', array(
//            'pagination' => $pagination,
            'rows' => $rows,
            'current_filters' => $this->current_filters,
            'filter_form' => $this->filter_form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
            'list_fields' => [
                'a.id' => [
                    'title' => 'ID',
                    'row_field' => 'id',
                    'sorting_field' => 'supplier.id',
                    'sortable' => true,
                ],
                'a.name' => [
                    'title' => 'Name',
                    'row_field' => 'name',
                    'sorting_field' => 'supplier.name',
                    'sortable' => true,
                ],
                'a.phone' => [
                    'title' => 'Phone',
                    'row_field' => 'phone',
                    'sorting_field' => 'supplier.phone',
                    'sortable' => false,
                ],
                'a.email' => [
                    'title' => 'Email',
                    'row_field' => 'email',
                    'sorting_field' => 'supplier.email',
                    'sortable' => false,
                ],
                'a.url' => [
                    'title' => 'URL',
                    'row_field' => 'url',
                    'sorting_field' => 'supplier.url',
                    'sortable' => false,
                ],
            ]
        ));
    }

    /**
     * Creates a new supplier entity.
     *
     * @Route("backend/supplier/new", name="backend_supplier_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, SupplierRepository $repository, EntityManagerInterface $em)
    {
        $supplier = new Supplier();
        $form = $this->createForm('App\Form\SupplierType', $supplier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($supplier);
            $em->flush();
            $this->addFlash('success', 'New record was created!');

            return $this->redirectToRoute('backend_supplier_edit', array('id' => $supplier->getId()));
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'Errors due creating object!');
        }

        return $this->render('admin/common/new.html.twig', array(
            'row' => $supplier,
            'form' => $form->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Displays a form to edit an existing supplier entity.
     *
     * @Route("backend/supplier/{id}/remove_offers", name="backend_supplier_remove_offers", methods={"GET"})
     */
    public function removeOffers(Request $request,
                                 Supplier $supplier,
                                 FileUploader $fileUploader)
    {
        /** @var Offer $offer */
        foreach ($supplier->getOffers() as $offer) {
            $offer->removeAllFoods();
            $this->em->remove($offer);
        }
        $this->em->flush();
        $this->addFlash('success', 'All Supplier offers are deleted!');

        return $this->redirectToRoute('backend_supplier_index');
    }

    /**
     * Displays a form to edit an existing supplier entity.
     *
     * @Route("backend/supplier/{id}/edit", name="backend_supplier_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Supplier $supplier, FileUploader $fileUploader, EntityManagerInterface $em)
    {
        $deleteForm = $this->createDeleteForm($supplier);
        $editForm = $this->createForm(SupplierType::class, $supplier);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            if (null !== $file = $supplier->getPicFile()) {
                $fileName = $fileUploader->upload($file,'supplier');
                $supplier->setPic($fileName);
            }

            $this->getDoctrine()->getManager()->persist($supplier);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Your changes were saved!');

            return $this->redirectToRoute('backend_supplier_edit', array('id' => $supplier->getId()));
        }

        if ($editForm->isSubmitted() && !$editForm->isValid()) {
            $this->addFlash('danger', 'Errors due saving object!');
        }

        return $this->render('admin/supplier/edit.html.twig', array(
            'row' => $supplier,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'model' => self::MODEL,
            'entity_name' => self::ENTITY_NAME,
        ));
    }

    /**
     * Deletes a supplier entity.
     *
     * @Route("backend/supplier/{id}", name="backend_supplier_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Supplier $supplier)
    {
        $filter_form = $this->createDeleteForm($supplier);
        $filter_form->handleRequest($request);

        if ($filter_form->isSubmitted() && $filter_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($supplier);
            $em->flush($supplier);

            $this->addFlash('success', 'Record was successfully deleted!');
        }

        if (!$filter_form->isValid()) {
            /** @var FormErrorIterator $errors */
            $errors = $filter_form->getErrors()->__toString();
            $this->addFlash('danger', 'Error due deletion! ' . $errors);
        }

        return $this->redirectToRoute('backend_supplier_index');
    }

    /**
     * Creates a form to delete a supplier entity.
     *
     * @param Supplier $supplier The supplier entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Supplier $supplier)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('backend_supplier_delete', array('id' => $supplier->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


}
