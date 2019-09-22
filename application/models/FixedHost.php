<?php
namespace models;

/** @Entity */
class FixedHost {
  /** @Id @Column(type="string", length=129) */
  private $ip;
  /** @Column(type="string", length=17) */
  private $mac;
  /** @Column(type="string") */
  private $name;

  function getIP() {
    return $this->ip;
  }
  function setIP($ip) {
    $this->ip = $ip;
  }
  function getMAC() {
    return $this->mac;
  }
  function setMAC($mac) {
    $this->mac = $mac;
  }
  function getName() {
    return $this->name;
  }
  function setName($name) {
    $this->name = $name;
  }
}
