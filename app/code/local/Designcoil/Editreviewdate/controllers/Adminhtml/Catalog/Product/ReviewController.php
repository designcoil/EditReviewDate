<?php
require_once 'Mage/Adminhtml/controllers/Catalog/Product/ReviewController.php';
class Designcoil_Editreviewdate_Adminhtml_Catalog_Product_ReviewController extends Mage_Adminhtml_Catalog_Product_ReviewController
{
    public function saveAction()
    {
        if (($data = $this->getRequest()->getPost()) && ($reviewId = $this->getRequest()->getParam('id'))) {
            $review = Mage::getModel('review/review')->load($reviewId);
            $session = Mage::getSingleton('adminhtml/session');
            if (! $review->getId()) {
                $session->addError(Mage::helper('catalog')->__('The review was removed by another user or does not exist.'));
            } else {
                try {
					$old_date = $data['created_at'];
					$new_date = date('F j, Y',strtotime($old_date . "+1 days"));
					$data['created_at'] = $new_date;
                    $review->addData($data)->save();

                    $arrRatingId = $this->getRequest()->getParam('ratings', array());
                    $votes = Mage::getModel('rating/rating_option_vote')
                        ->getResourceCollection()
                        ->setReviewFilter($reviewId)
                        ->addOptionInfo()
                        ->load()
                        ->addRatingOptions();
                    foreach ($arrRatingId as $ratingId=>$optionId) {
                        if($vote = $votes->getItemByColumnValue('rating_id', $ratingId)) {
                            Mage::getModel('rating/rating')
                                ->setVoteId($vote->getId())
                                ->setReviewId($review->getId())
                                ->updateOptionVote($optionId);
                        } else {
                            Mage::getModel('rating/rating')
                                ->setRatingId($ratingId)
                                ->setReviewId($review->getId())
                                ->addOptionVote($optionId, $review->getEntityPkValue());
                        }
                    }

                    $review->aggregate();
                    $session->addSuccess(Mage::helper('catalog')->__('The review has been saved.'));
                } catch (Mage_Core_Exception $e) {
                    $session->addError($e->getMessage());
                } catch (Exception $e){
                    $session->addException($e, Mage::helper('catalog')->__('An error occurred while saving this review.'));
                }
            }

            return $this->getResponse()->setRedirect($this->getUrl($this->getRequest()->getParam('ret') == 'pending' ? '*/*/pending' : '*/*/'));
        }
        $this->_redirect('*/*/');
    }
}
