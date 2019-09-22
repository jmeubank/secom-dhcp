<?php
namespace models;

/** @Entity */
class Network {
  /** @Id @Column(type="bigint") @GeneratedValue */
  private $id;
  /** @Column(type="string") */
  private $name;

  function getId() {
    return $this->id;
  }

  function getName() {
    return $this->name;
  }
  function setName($name) {
    $this->name = $name;
  }
}
