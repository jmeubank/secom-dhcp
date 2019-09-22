<?php
namespace models;

/** @Entity */
class Pool {
  /** @Id @Column(type="string", length=129) */
  private $start;
  /** @Column(type="string", length=129) */
  private $end;

  /** @ManyToOne(targetEntity="Subnet") @JoinColumn(name="subnet_address", referencedColumnName="address") */
  private $subnet;

  function getStart() {
    return $this->start;
  }
  function setStart($start) {
    $this->start = $start;
  }
  function getEnd() {
    return $this->end;
  }
  function setEnd($end) {
    $this->end = $end;
  }

  function getSubnet() {
    return $this->subnet;
  }
  function setSubnet($subnet) {
    $this->subnet = $subnet;
  }
}
