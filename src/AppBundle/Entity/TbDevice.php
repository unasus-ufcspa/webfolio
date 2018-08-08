<?php

namespace AppBundle\Entity;

/**
 * TbDevice
 */
class TbDevice
{
    /**
     * @var integer
     */
    private $idDevice;

    /**
     * @var string
     */
    private $tpDevice;


    /**
     * @var \AppBundle\Entity\TbUser
     */
    private $idUser;


    /**
     * Get idDevice
     *
     * @return integer
     */
    public function getIdDevice()
    {
        return $this->idDevice;
    }

    /**
     * Set tpDevice
     *
     * @param string $tpDevice
     *
     * @return TbDevice
     */
    public function setTpDevice($tpDevice)
    {
        $this->tpDevice = $tpDevice;

        return $this;
    }

    /**
     * Get tpDevice
     *
     * @return string
     */
    public function getTpDevice()
    {
        return $this->tpDevice;
    }



    /**
     * Set idUser
     *
     * @param \AppBundle\Entity\TbUser $idUser
     *
     * @return TbDevice
     */
    public function setIdUser(\AppBundle\Entity\TbUser $idUser = null)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return \AppBundle\Entity\TbUser
     */
    public function getIdUser()
    {
        return $this->idUser;
    }
    /**
     * @var string
     */
    private $flFirstLogin;

    /**
     * @var string
     */
    private $flBasicData;


    /**
     * Set flFirstLogin
     *
     * @param string $flFirstLogin
     *
     * @return TbDevice
     */
    public function setFlFirstLogin($flFirstLogin)
    {
        $this->flFirstLogin = $flFirstLogin;

        return $this;
    }

    /**
     * Get flFirstLogin
     *
     * @return string
     */
    public function getFlFirstLogin()
    {
        return $this->flFirstLogin;
    }

    /**
     * Set flBasicData
     *
     * @param string $flBasicData
     *
     * @return TbDevice
     */
    public function setFlBasicData($flBasicData)
    {
        $this->flBasicData = $flBasicData;

        return $this;
    }

    /**
     * Get flBasicData
     *
     * @return string
     */
    public function getFlBasicData()
    {
        return $this->flBasicData;
    }
    /**
     * @var string
     */
    private $dsHash;

    /**
     * @var \DateTime
     */
    private $dtFirstLogin;

    /**
     * @var \DateTime
     */
    private $dtBasicData;

    /**
     * @var \DateTime
     */
    private $dtLogout;


    /**
     * Set dsHash
     *
     * @param string $dsHash
     *
     * @return TbDevice
     */
    public function setDsHash($dsHash)
    {
        $this->dsHash = $dsHash;

        return $this;
    }

    /**
     * Get dsHash
     *
     * @return string
     */
    public function getDsHash()
    {
        return $this->dsHash;
    }

    /**
     * Set dtFirstLogin
     *
     * @param \DateTime $dtFirstLogin
     *
     * @return TbDevice
     */
    public function setDtFirstLogin($dtFirstLogin)
    {
        $this->dtFirstLogin = $dtFirstLogin;

        return $this;
    }

    /**
     * Get dtFirstLogin
     *
     * @return \DateTime
     */
    public function getDtFirstLogin()
    {
        return $this->dtFirstLogin;
    }

    /**
     * Set dtBasicData
     *
     * @param \DateTime $dtBasicData
     *
     * @return TbDevice
     */
    public function setDtBasicData($dtBasicData)
    {
        $this->dtBasicData = $dtBasicData;

        return $this;
    }

    /**
     * Get dtBasicData
     *
     * @return \DateTime
     */
    public function getDtBasicData()
    {
        return $this->dtBasicData;
    }

    /**
     * Set dtLogout
     *
     * @param \DateTime $dtLogout
     *
     * @return TbDevice
     */
    public function setDtLogout($dtLogout)
    {
        $this->dtLogout = $dtLogout;

        return $this;
    }

    /**
     * Get dtLogout
     *
     * @return \DateTime
     */
    public function getDtLogout()
    {
        return $this->dtLogout;
    }
}
