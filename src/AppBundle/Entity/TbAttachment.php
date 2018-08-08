<?php

namespace AppBundle\Entity;

/**
 * TbAttachment
 */
class TbAttachment
{
    /**
     * @var integer
     */
    private $idAttachment;

    /**
     * @var string
     */
    private $dsLocalPath;

    /**
     * @var string
     */
    private $dsServerPath;

    /**
     * @var string
     */
    private $tpAttachment;

    /**
     * @var string
     */
    private $nmFile;

    /**
     * @var integer
     */
    private $idAttachmentSrv;

    /**
     * @var \AppBundle\Entity\TbActivityStudent
     */
    private $idActivityStudent;

    /**
     * @var \AppBundle\Entity\TbComment
     */
    private $idComment;


    /**
     * Get idAttachment
     *
     * @return integer
     */
    public function getIdAttachment()
    {
        return $this->idAttachment;
    }

    /**
     * Set dsLocalPath
     *
     * @param string $dsLocalPath
     *
     * @return TbAttachment
     */
    public function setDsLocalPath($dsLocalPath)
    {
        $this->dsLocalPath = $dsLocalPath;

        return $this;
    }

    /**
     * Get dsLocalPath
     *
     * @return string
     */
    public function getDsLocalPath()
    {
        return $this->dsLocalPath;
    }

    /**
     * Set dsServerPath
     *
     * @param string $dsServerPath
     *
     * @return TbAttachment
     */
    public function setDsServerPath($dsServerPath)
    {
        $this->dsServerPath = $dsServerPath;

        return $this;
    }

    /**
     * Get dsServerPath
     *
     * @return string
     */
    public function getDsServerPath()
    {
        return $this->dsServerPath;
    }

    /**
     * Set tpAttachment
     *
     * @param string $tpAttachment
     *
     * @return TbAttachment
     */
    public function setTpAttachment($tpAttachment)
    {
        $this->tpAttachment = $tpAttachment;

        return $this;
    }

    /**
     * Get tpAttachment
     *
     * @return string
     */
    public function getTpAttachment()
    {
        return $this->tpAttachment;
    }

    /**
     * Set nmFile
     *
     * @param string $nmFile
     *
     * @return TbAttachment
     */
    public function setNmFile($nmFile)
    {
        $this->nmFile = $nmFile;

        return $this;
    }

    /**
     * Get nmFile
     *
     * @return string
     */
    public function getNmFile()
    {
        return $this->nmFile;
    }

    /**
     * Set idAttachmentSrv
     *
     * @param integer $idAttachmentSrv
     *
     * @return TbAttachment
     */
    public function setIdAttachmentSrv($idAttachmentSrv)
    {
        $this->idAttachmentSrv = $idAttachmentSrv;

        return $this;
    }

    /**
     * Get idAttachmentSrv
     *
     * @return integer
     */
    public function getIdAttachmentSrv()
    {
        return $this->idAttachmentSrv;
    }

    /**
     * Set idActivityStudent
     *
     * @param \AppBundle\Entity\TbActivityStudent $idActivityStudent
     *
     * @return TbAttachment
     */
    public function setIdActivityStudent(\AppBundle\Entity\TbActivityStudent $idActivityStudent = null)
    {
        $this->idActivityStudent = $idActivityStudent;

        return $this;
    }

    /**
     * Get idActivityStudent
     *
     * @return \AppBundle\Entity\TbActivityStudent
     */
    public function getIdActivityStudent()
    {
        return $this->idActivityStudent;
    }

    /**
     * Set idComment
     *
     * @param \AppBundle\Entity\TbComment $idComment
     *
     * @return TbAttachment
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
    private $nmSystem;


    /**
     * Set nmSystem
     *
     * @param string $nmSystem
     *
     * @return TbAttachment
     */
    public function setNmSystem($nmSystem)
    {
        $this->nmSystem = $nmSystem;

        return $this;
    }

    /**
     * Get nmSystem
     *
     * @return string
     */
    public function getNmSystem()
    {
        return $this->nmSystem;
    }
    /**
     * @var \AppBundle\Entity\TbUser
     */
    private $idAuthor;


    /**
     * Set idAuthor
     *
     * @param \AppBundle\Entity\TbUser $idAuthor
     *
     * @return TbAttachment
     */
    public function setIdAuthor(\AppBundle\Entity\TbUser $idAuthor = null)
    {
        $this->idAuthor = $idAuthor;

        return $this;
    }

    /**
     * Get idAuthor
     *
     * @return \AppBundle\Entity\TbUser
     */
    public function getIdAuthor()
    {
        return $this->idAuthor;
    }
}
