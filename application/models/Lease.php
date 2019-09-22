<?php
namespace models;

/** @Entity
 *  @Table(indexes={@index(name="query1_idx", columns={"expired", "ip", "mac"})})
 */
class Lease {
  /** @Id @Column(type="string", length=129) */
  private $ip;
  /** @Id @Column(type="datetime") */
  private $begin;
  /** @Column(type="datetime") */
  private $end;
  /** @Column(type="boolean") */
  private $expired;
  /** @Column(type="string", length=17) */
  private $mac;
  /** @Column(type="string") */
  private $hostname;
  /** @Column(type="datetime") */
  private $last_renewal;
}
