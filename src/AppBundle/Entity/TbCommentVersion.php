<?php

namespace AppBundle\Entity;

/**
 * TbCommentVersion
 */
class TbCommentVersion
{
    /**
     * @var integer
     */
    private $idCommentVersion;

    /**
     * @var string
     */
    private $flActive;

    /**
     * @var integer
     */
    private $idVersionActivity;

    /**
     * @var \AppBundle\Entity\TbComment
     */
    private $idComment;


    /**
     * Get idCommentVersion
     *
     * @return integer
     */
    public function getIdCommentVersion()
    {
        return $this->idCommentVersion;
    }

    /**
     * Set flActive
     *
     * @param string $flActive
     *
     * @return TbCommentVersion
     */
    public function setFlActive($flActive)
    {
        $this->flActive = $flActive;

        return $this;
    }

    /**
     * Get flActive
     *
     * @return string
     */
    public function getFlActive()
    {
        return $this->flActive;
    }

    /**
     * Set idVersionActivity
     *
     * @param integer
     *
     * @return TbCommentVersion
     */
    public function setIdVersionActivity($idVersionActivity)
    {
        $this->idVersionActivity = $idVersionActivity;

        return $this;
    }

     /**
     * Get idVersionActivity
     *
     * @return TbCommentVersion
     */
    public function getIdVersionActivity()
    {
        return $this->idVersionActivity;
    }

    /**
     * Set idComment
     *
     * @param \AppBundle\Entity\TbComment $idComment
     *
     * @return TbCommentVersion
     */
    public function setIdComment(\AppBundle\Entity\TbComment $idComment = null)
    {
        $this->idComment = $idComment;

        return $this;
    }

    /**
     * Get idComment
     *
     * @return \AppBundle\Entity\TbComment
     */
    public function getIdComment()
    {
        return $this->idComment;
    }
    /**
     * @var string
     */
    private $txReference;

    /**
     * @var integer
     */
    private $nuCommentActivity;

    /**
     * @var integer
     */
    private $nuInitialPos;

    /**
     * @var integer
     */
    private $nuSize;


    /**
     * Set txReference
     *
     * @param string $txReference
     *
     * @return TbCommentVersion
     */
    public function setTxReference($txReference)
    {
        $this->txReference = $txReference;

        return $this;
    }

    /**
     * Get txReference
     *
     * @return string
     */
    public function getTxReference()
    {
        return $this->txReference;
    }

    /**
     * Set nuCommentActivity
     *
     * @param integer $nuCommentActivity
     *
     * @return TbCommentVersion
     */
    public function setNuCommentActivity($nuCommentActivity)
    {
        $this->nuCommentActivity = $nuCommentActivity;

        return $this;
    }

    /**
     * Get nuCommentActivity
     *
     * @return integer
     */
    public function getNuCommentActivity()
    {
        return $this->nuCommentActivity;
    }

    /**
     * Set nuInitialPos
     *
     * @param integer $nuInitialPos
     *
     * @return TbCommentVersion
     */
    public function setNuInitialPos($nuInitialPos)
    {
        $this->nuInitialPos = $nuInitialPos;

        return $this;
    }

    /**
     * Get nuInitialPos
     *
     * @return integer
     */
    public function getNuInitialPos()
    {
        return $this->nuInitialPos;
    }

    /**
     * Set nuSize
     *
     * @param integer $nuSize
     *
     * @return TbCommentVersion
     */
    public function setNuSize($nuSize)
    {
        $this->nuSize = $nuSize;

        return $this;
    }

    /**
     * Get nuSize
     *
     * @return integer
     */
    public function getNuSize()
    {
        return $this->nuSize;
    }
    /**
     * @var integer
     */
    private $idCommentVersionSrv;


    /**
     * Set idCommentVersionSrv
     *
     * @param integer $idCommentVersionSrv
     *
     * @return TbCommentVersion
     */
    public function setIdCommentVersionSrv($idCommentVersionSrv)
    {
        $this->idCommentVersionSrv = $idCommentVersionSrv;

        return $this;
    }

    /**
     * Get idCommentVersionSrv
     *
     * @return integer
     */
    public function getIdCommentVersionSrv()
    {
        return $this->idCommentVersionSrv;
    }
    /**
     * @var integer
     */
    private $flSrv;


    /**
     * Set flSrv
     *
     * @param integer $flSrv
     *
     * @return TbCommentVersion
     */
    public function setFlSrv($flSrv)
    {
        $this->flSrv = $flSrv;

        return $this;
    }

    /**
     * Get flSrv
     *
     * @return integer
     */
    public function getFlSrv()
    {
        return $this->flSrv;
    }
}
