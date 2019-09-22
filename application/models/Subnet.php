<?php
namespace models;

/** @Entity */
class Subnet {
  /** @Id @Column(type="string", length=129) */
  private $address;
  /** @Column(type="string", length=39, nullable=true) */
  private $gateway;
  /** @Column(type="text", nullable=true) */
  private $addl_config;
  /** @Column(type="text", nullable=true) */
  private $addl_pool_config;
  /** @Column(type="smallint") */
  private $snmp_instance;

  /** @ManyToOne(targetEntity="Network") */
  private $network;

  function getAddress() {
    return $this->address;
  }
  function setAddress($address) {
    $this->address = $address;
  }
  function getGateway() {
    return $this->gateway;
  }
  function setGateway($gateway) {
    $this->gateway = $gateway;
  }
  function getAddlConfig() {
    return $this->addl_config;
  }
  function setAddlConfig($addl_config) {
    $this->addl_config = $addl_config;
  }
  function setAddlPoolConfig($addl_pool_config) {
    $this->addl_pool_config = $addl_pool_config;
  }
  function getSNMPInstance() {
    return $this->snmp_instance;
  }
  function setSNMPInstance($snmp_instance) {
    $this->snmp_instance = $snmp_instance;
  }

  function getNetwork() {
    return $this->network;
  }
  function setNetwork($network) {
    $this->network = $network;
  }
}
