<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
use Doctrine\ORM\Query;

class AppData extends CI_Controller {
  function networktree2($obj_id) {
    try {
      if ($obj_id === 'network_root') {
        $networks = $this->doctrine->em->createQuery(
          'SELECT n.id, n.name FROM models\Network n ORDER BY n.name ASC'
        )->getResult(Query::HYDRATE_ARRAY);
        foreach ($networks as &$n) {
          $children = $this->doctrine->em->createQuery(
            'SELECT COUNT(s.address) FROM models\Subnet s WHERE s.network = ?1'
          )->setParameter(1, $n['id'])
          ->getSingleScalarResult();
          if ($children > 0)
            $n['children'] = array();
          $n['name'] = 'Network: ' . $n['name'];
          $n['id'] = 'network-' . $n['id'];
        }
        echo json_encode($networks);
        return;
      } else if (substr($obj_id, 0, 8) == 'network-') {
        $this->load->helper('ip');
        $subnets = $this->doctrine->em->createQuery(
          'SELECT s.address FROM models\Subnet s WHERE s.network = ?1'
        )->setParameter(1, substr($obj_id, 8))
        ->getResult(Query::HYDRATE_ARRAY);
        foreach ($subnets as &$s) {
          $children = $this->doctrine->em->createQuery(
            'SELECT COUNT(p.start) FROM models\Pool p WHERE p.subnet = ?1'
          )->setParameter(1, $s['address'])
          ->getSingleScalarResult();
          if ($children > 0)
            $s['children'] = array();
          else {
            $children = $this->doctrine->em->createQuery(
              'SELECT COUNT(h.ip) FROM models\FixedHost h WHERE h.ip LIKE ?1'
            )->setParameter(1, $s['address'])
            ->getSingleScalarResult();
            if ($children > 0)
              $s['children'] = array();
          }
          $is_ipv6 = (boolean)($s['address'][0] == '1');
          $saddr = bitcard2addrmask(substr($s['address'], 1), $is_ipv6);
          $s['name'] = 'Subnet: ' . int2ip($saddr[0], $is_ipv6) . '/' . $saddr[1];
          $s['id'] = 'subnet-' . substr($s['address'], 0, strlen($s['address']) - 1);
        }
        echo json_encode(array('children' => $subnets));
        return;
      } else if (substr($obj_id, 0, 7) == 'subnet-') {
        $this->load->helper('ip');
        $children = $this->doctrine->em->createQuery(
          'SELECT p.start, p.end FROM models\Pool p WHERE p.subnet = ?1'
        )->setParameter(1, substr($obj_id, 7) . '%')
        ->getResult(Query::HYDRATE_ARRAY);
        foreach ($children as &$p) {
          $is_ipv6 = (boolean)($p['start'][0] == '1');
          $saddr = bitcard2addrmask(substr($p['start'], 1), $is_ipv6);
          $eaddr = bitcard2addrmask(substr($p['end'], 1), $is_ipv6);
          $p['name'] = 'Pool: ' . int2ip($saddr[0], $is_ipv6) . ' - '
          . int2ip($eaddr[0], $is_ipv6);
          $p['id'] = 'pool-' . $p['start'];
        }
        $fixedhosts = $this->doctrine->em->createQuery(
          'SELECT h.ip, h.name FROM models\FixedHost h WHERE h.ip LIKE ?1'
        )->setParameter(1, substr($obj_id, 7) . '%')
        ->getResult(Query::HYDRATE_ARRAY);
        foreach ($fixedhosts as $h) {
          $is_ipv6 = (boolean)($h['ip'][0] == '1');
          $haddr = bitcard2addrmask(substr($h['ip'], 1), $is_ipv6);
          $children[] = array(
            'id' => 'fixed-' . $h['ip'],
            'name' => 'Static: ' . int2ip($haddr[0], $is_ipv6) . ' ' . $h['name']
          );
        }
        echo json_encode(array('children' => $children));
        return;
      }
    } catch (Exception $e) {
      return jsonize_error($e->getMessage());
    }
  }

  function networktree() {
    try {
      $this->load->helper('ip');
      $networks = $this->doctrine->em->createQuery(
        'SELECT n FROM models\Network n ORDER BY n.name ASC'
      )->getResult(Query::HYDRATE_ARRAY);
      foreach ($networks as &$network) {
        $nid = $network['id'];
        $network['id'] = 'network-' . $network['id'];
        $network['name'] = 'Network: ' . $network['name'];
        $network['children'] = $this->doctrine->em->createQuery(
          'SELECT s FROM models\Subnet s WHERE s.network = ?1'
        )->setParameter(1, $nid)
        ->getResult(Query::HYDRATE_ARRAY);
        foreach ($network['children'] as &$subnet) {
          $is_ipv6 = (boolean)($subnet['address'][0] == '1');
          $s = bitcard2addrmask(substr($subnet['address'], 1), $is_ipv6);
          $saddr = int2ip($s[0], $is_ipv6);
          $subnet['id'] = 'subnet-' . substr($subnet['address'], 0, strlen($subnet['address']) - 1);
          $subnet['name'] = 'Subnet: ' . $saddr . '/' . $s[1];
          $subnet['children'] = $this->doctrine->em->createQuery(
            'SELECT p FROM models\Pool p WHERE p.subnet = ?1'
          )->setParameter(1, $subnet['address'])
          ->getResult(Query::HYDRATE_ARRAY);
          foreach ($subnet['children'] as &$pool) {
            $is_ipv6 = (boolean)($pool['start'][0] == '1');
            $ps = bitcard2addrmask(substr($pool['start'], 1), $is_ipv6);
            $pe = bitcard2addrmask(substr($pool['end'], 1), $is_ipv6);
            $psaddr = int2ip($ps[0], $is_ipv6);
            $pool['id'] = 'pool-' . $pool['start'];
            $pool['name'] = 'Pool: ' . $psaddr . ' - ' . int2ip($pe[0], $is_ipv6);
          }
          $fixed = $this->doctrine->em->createQuery(
            'SELECT h FROM models\FixedHost h WHERE h.ip LIKE ?1'
          )->setParameter(1, $subnet['address'])
          ->iterate();
          foreach ($fixed as $fh) {
            $ipc = $fh[0]->getIP();
            $is_ipv6 = (boolean)($ipc[0] == '1');
            $addr = bitcard2addrmask(substr($ipc, 1), $is_ipv6);
            $subnet['children'][] = array(
              'id' => 'fixed-' . $fh[0]->getIP(),
              'name' => 'Static: ' . int2ip($addr[0], $is_ipv6) . ' ' . $fh[0]->getName()
            );
          }
        }
      }
      echo json_encode(array(
        'identifier' => 'id',
        'label' => 'name',
        'items' => $networks
      ));
      return;
    } catch (Exception $e) {
      return jsonize_error($e->getMessage());
    }
  }

  function navpath($obj_id) {
    try {
      $find = $obj_id;
      $id_array = array();
      while (true) {
        if (substr($find, 0, 5) == 'pool-') {
          $s = $this->doctrine->em->createQuery(
            'SELECT s.address FROM models\Pool p JOIN p.subnet s WHERE p.start = ?1'
          )->setParameter(1, substr($find, 5))
          ->getSingleResult(Query::HYDRATE_ARRAY);
          array_unshift($id_array, $find);
          $find = 'subnet-' . substr($s['address'], 0, strlen($s['address']) - 1);
          continue;
        } else if (substr($find, 0, 6) == 'fixed-') {
          $s = $this->doctrine->em->getConnection()->query(
            "SELECT address FROM Subnet WHERE '" . substr($find, 6) . "' LIKE address;"
          );
          array_unshift($id_array, $find);
          $f = $s->fetchAll();
          if (count($f) != 1)
            return jsonize_error("No parent for " . $find);
          $find = 'subnet-' . substr($f[0]['address'], 0, strlen($f[0]['address']) - 1);
          continue;
        } else if (substr($find, 0, 7) == 'subnet-') {
          $n = $this->doctrine->em->createQuery(
            'SELECT n.id FROM models\Subnet s LEFT JOIN s.network n WHERE s.address = ?1'
          )->setParameter(1, substr($find, 7) . '%')
          ->getSingleResult(Query::HYDRATE_ARRAY);
          array_unshift($id_array, $find);
          $find = 'network-' . $n['id'];
          continue;
        } else if (substr($find, 0, 8) == 'network-') {
          array_unshift($id_array, $find);
          $find = 'network_root';
          continue;
        } else if ($find == 'network_root') {
          array_unshift($id_array, $find);
          break;
        } else
          return jsonize_error("Invalid navpath object id: " . $find);
      }
      return jsonize_success(array('ids' => $id_array));
    } catch (Exception $e) {
      return jsonize_error($e->getMessage());
    }
  }

  function currentleases($obj_id) {
    try {
      $this->load->helper('ip');
      $currentleases = array();
      if ($obj_id === 'network_root') {
        $currentleases[] = $this->doctrine->em->createQuery(
          'SELECT l FROM models\Lease l WHERE l.expired = FALSE'
        )->getResult(Query::HYDRATE_ARRAY);
      } else if (substr($obj_id, 0, 8) === 'network-') {
        $subnets = $this->doctrine->em->createQuery(
          'SELECT s.address FROM models\Subnet s WHERE s.network = ?1'
        )->setParameter(1, substr($obj_id, 8))
        ->getResult(Query::HYDRATE_ARRAY);
        foreach ($subnets as $s) {
          $cl1 = $this->doctrine->em->createQuery(
            'SELECT l FROM models\Lease l WHERE l.expired = FALSE AND l.ip LIKE ?1'
          )->setParameter(1, $s['address'])
          ->getResult(Query::HYDRATE_ARRAY);
          $currentleases[] = $cl1;
        }
      } else if (substr($obj_id, 0, 7) === 'subnet-') {
        $cl1 = $this->doctrine->em->createQuery(
          'SELECT l FROM models\Lease l WHERE l.expired = FALSE AND l.ip LIKE ?1'
        )->setParameter(1, substr($obj_id, 7) . '%')
        ->getResult(Query::HYDRATE_ARRAY);
        $currentleases[] = $cl1;
      } else if (substr($obj_id, 0, 5) === 'pool-') {
        $p = $this->doctrine->em->createQuery(
          'SELECT p FROM models\Pool p WHERE p.start = ?1'
        )->setParameter(1, substr($obj_id, 5))
        ->getSingleResult(Query::HYDRATE_ARRAY);
        $cl1 = $this->doctrine->em->createQuery(
          'SELECT l FROM models\Lease l WHERE l.expired = FALSE AND l.ip BETWEEN ?1 AND ?2'
        )->setParameter(1, $p['start'])
        ->setParameter(2, $p['end'])
        ->getResult(Query::HYDRATE_ARRAY);
        $currentleases[] = $cl1;
      } else
        return jsonize_error("Invalid obj_id: " . $obj_id);
      $clfinal = array();
      foreach ($currentleases as &$clarray) {
        foreach ($clarray as &$cl) {
          $is_ipv6 = (boolean)($cl['ip'][0] == '1');
          $l = bitcard2addrmask(substr($cl['ip'], 1), $is_ipv6);
          $cl['ip'] = int2ip($l[0], $is_ipv6);
          $cl['begin'] = $cl['begin']->format('Y-m-d H:i:s');
          $cl['end'] = $cl['end']->format('Y-m-d H:i:s');
          $cl['last_renewal'] = $cl['last_renewal']->format('Y-m-d H:i:s');
          $clfinal[] = $cl;
        }
      }
      echo json_encode(array(
        'identifier' => 'ip',
        'label' => 'ip',
        'items' => $clfinal
      ));
      return;
    } catch (Exception $e) {
      return jsonize_error($e->getMessage());
    }
  }

  function network($action) {
    try {
      if ($action == 'create') {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', "\n");
        $this->form_validation->set_rules('name', 'Name',
        'required|trim|max_length[254]');
        if (!$this->form_validation->run())
          return jsonize_error(validation_errors());
        $n = new models\Network;
        $n->setName($this->input->post('name'));
        $this->doctrine->em->persist($n);
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        return jsonize_success(array('obj_id' => 'network-' . $n->getId()));
      } else if ($action == 'update') {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', "\n");
        $this->form_validation->set_rules('network_id', '', 'required');
        $this->form_validation->set_rules('name', 'Name',
        'required|trim|max_length[254]');
        if (!$this->form_validation->run())
          return jsonize_error(validation_errors());
        $n = $this->doctrine->em->createQuery(
          'SELECT n FROM models\Network n WHERE n.id = ?1'
        )->setParameter(1, $this->input->post('network_id'))
        ->getSingleResult();
        $n->setName($this->input->post('name'));
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        return jsonize_success(array());
      } else if ($action == 'delete') {
        $network = $this->doctrine->em->createQuery(
          'SELECT n FROM models\Network n WHERE n.id = ?1'
        )->setParameter(1, $this->input->post('id'))
        ->getSingleResult();
        $subnets = $this->doctrine->em->createQuery(
          'SELECT s FROM models\Subnet s WHERE s.network = ?1'
        )->setParameter(1, $this->input->post('id'))
        ->iterate();
        foreach ($subnets as $subnet) {
          $pools = $this->doctrine->em->createQuery(
            'SELECT p FROM models\Pool p WHERE p.subnet = ?1'
          )->setParameter(1, $subnet[0]->getAddress())
          ->iterate();
          foreach ($pools as $pool)
            $this->doctrine->em->remove($pool[0]);
          $this->doctrine->em->remove($subnet[0]);
        }
        $this->doctrine->em->remove($network);
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        return jsonize_success(array());
      } else
        return jsonize_error("Invalid action: " . $action);
    } catch (Exception $e) {
      return jsonize_error($e->getMessage());
    }
  }

  function subnet($action) {
    try {
      if ($action == 'create') {
        $this->load->helper('ip');
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', "\n");
        $this->form_validation->set_rules('network_id', '', 'required|is_natural');
        $this->form_validation->set_rules('address', 'Network address',
        'required|trim|valid_ip');
        $this->form_validation->set_rules('slash', 'Prefix length',
        'required|trim|is_natural|less_than[129]');
        if (!$this->form_validation->run())
          return jsonize_error(validation_errors());
        $addr = '0' . ipint2bits(ip2int($this->input->post('address'), FALSE),
        FALSE, (integer)$this->input->post('slash'));
        $n = $this->doctrine->em->getConnection()->query(
          "SELECT address FROM Subnet WHERE '" . $addr . "' LIKE address OR address LIKE '" . $addr . "';"
        );
        if ($n->rowCount() > 0) {
          return jsonize_error("The subnet '" . $this->input->post('address')
          . '/' . $this->input->post('slash') . "' overlaps with an existing subnet");
        }
        $s = new models\Subnet;
        $n = $this->doctrine->em->createQuery(
          'SELECT n FROM models\Network n WHERE n.id = ?1'
        )->setParameter(1, $this->input->post('network_id'))
        ->getSingleResult();
        $s->setNetwork($n);
        $s->setAddress($addr);
        $s->setAddlConfig(NULL);
        $max_inst = $this->doctrine->em->createQuery(
          'SELECT MAX(s.snmp_instance) FROM models\Subnet s'
        )->getSingleScalarResult();
        $s->setSNMPInstance($max_inst + 1);
        $this->doctrine->em->persist($s);
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        return jsonize_success(array(
          'obj_id' => 'subnet-' . substr($addr, 0, strlen($addr) - 1)
        ));
      } else if ($action == 'update') {
        $this->load->helper('ip');
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', "\n");
        $this->form_validation->set_rules('subnet_address', '', 'required');
        $this->form_validation->set_rules('gateway', 'Gateway IP',
        'trim|valid_ip');
        if (!$this->form_validation->run())
          return jsonize_error(validation_errors());
        $s = $this->doctrine->em->createQuery(
          'SELECT s FROM models\Subnet s WHERE s.address = ?1'
        )->setParameter(1, $this->input->post('subnet_address'))
        ->getSingleResult();
        if ($this->input->post('gateway')) {
          $gaddr = '0' . ipint2bits(ip2int($this->input->post('gateway'), FALSE),
          FALSE);
          if (substr($s->getAddress(), 0, strlen($s->getAddress()) - 1) !==
          substr($gaddr, 0, strlen($s->getAddress()) - 1))
            return jsonize_error("The gateway IP must be within the subnet");
          $s->setGateway($this->input->post('gateway'));
        } else
          $s->setGateway(NULL);
        $s->setAddlConfig($this->input->post('addl_config') ? $this->input->post('addl_config') : NULL);
        $s->setAddlPoolConfig($this->input->post('addl_pool_config') ? $this->input->post('addl_pool_config') : NULL);
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        return jsonize_success(array());
      } else if ($action == 'delete') {
        $subnet = $this->doctrine->em->createQuery(
          'SELECT s FROM models\Subnet s WHERE s.address = ?1'
        )->setParameter(1, $this->input->post('address'))
        ->getSingleResult();
        $pools = $this->doctrine->em->createQuery(
          'SELECT p FROM models\Pool p WHERE p.subnet = ?1'
        )->setParameter(1, $this->input->post('address'))
        ->iterate();
        foreach ($pools as $pool)
          $this->doctrine->em->remove($pool[0]);
        $this->doctrine->em->remove($subnet);
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        return jsonize_success(array());
      } else
        return jsonize_error("Invalid action: " . $action);
    } catch (Exception $e) {
      return jsonize_error($e->getMessage());
    }
  }

  function pool($action) {
    try {
      if ($action == 'create') {
        $this->load->helper('ip');
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', "\n");
        $this->form_validation->set_rules('subnet_address', '', 'required');
        $this->form_validation->set_rules('start', 'Pool start IP', 'required|trim|valid_ip');
        $this->form_validation->set_rules('end', 'Pool end IP', 'required|trim|valid_ip');
        if (!$this->form_validation->run())
          return jsonize_error(validation_errors());
        $s = $this->doctrine->em->createQuery(
          'SELECT s FROM models\Subnet s WHERE s.address = ?1'
        )->setParameter(1, $this->input->post('subnet_address'))
        ->getSingleResult();
        $saddr = '0' . ipint2bits(ip2int($this->input->post('start'), FALSE),
        FALSE);
        if (substr($s->getAddress(), 0, strlen($s->getAddress()) - 1) !==
        substr($saddr, 0, strlen($s->getAddress()) - 1))
          return jsonize_error("The pool start IP must be within the subnet");
        $eaddr = '0' . ipint2bits(ip2int($this->input->post('end'), FALSE),
        FALSE);
        if (substr($s->getAddress(), 0, strlen($s->getAddress()) - 1) !==
        substr($eaddr, 0, strlen($s->getAddress()) - 1))
          return jsonize_error("The pool end IP must be within the subnet");
        $p = new models\Pool;
        $p->setSubnet($s);
        $p->setStart($saddr);
        $p->setEnd($eaddr);
        $this->doctrine->em->persist($p);
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        return jsonize_success(array());
      } else if ($action == 'update') {
        $this->load->helper('ip');
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', "\n");
        $this->form_validation->set_rules('start_orig', '', 'required');
        $this->form_validation->set_rules('start_new', 'Pool start IP', 'required|trim|valid_ip');
        $this->form_validation->set_rules('end', 'Pool end IP', 'required|trim|valid_ip');
        if (!$this->form_validation->run())
          return jsonize_error(validation_errors());
        $start_orig = $this->input->post('start_orig');
        $p = $this->doctrine->em->createQuery(
          'SELECT p FROM models\Pool p WHERE p.start = ?1'
        )->setParameter(1, $start_orig)
        ->getSingleResult();
        $saddr = '0' . ipint2bits(ip2int($this->input->post('start_new'), FALSE),
        FALSE);
        if (substr($p->getSubnet()->getAddress(), 0, strlen($p->getSubnet()->getAddress()) - 1) !==
        substr($saddr, 0, strlen($p->getSubnet()->getAddress()) - 1))
          return jsonize_error("The pool start IP must be within the subnet");
        $eaddr = '0' . ipint2bits(ip2int($this->input->post('end'), FALSE),
        FALSE);
        if (substr($p->getSubnet()->getAddress(), 0, strlen($p->getSubnet()->getAddress()) - 1) !==
        substr($eaddr, 0, strlen($p->getSubnet()->getAddress()) - 1))
          return jsonize_error("The pool end IP must be within the subnet");
        $sid = NULL;
        $s = $p->getSubnet();
        $this->doctrine->em->remove($p);
        $this->doctrine->em->flush();
        $p = new models\Pool;
        $p->setSubnet($s);
        $p->setStart($saddr);
        $p->setEnd($eaddr);
        $this->doctrine->em->persist($p);
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        $sid = $p->getSubnet()->getAddress();
        return jsonize_success(array(
          'obj_id' => 'subnet-' . substr($sid, 0, strlen($sid) - 1)
        ));
      } else if ($action == 'delete') {
        $pool = $this->doctrine->em->createQuery(
          'SELECT p FROM models\Pool p WHERE p.start = ?1'
        )->setParameter(1, $this->input->post('start'))
        ->getSingleResult();
        $this->doctrine->em->remove($pool);
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        return jsonize_success(array());
      } else
        return jsonize_error("Invalid action: " . $action);
    } catch (Exception $e) {
      return jsonize_error($e->getMessage());
    }
  }

  function fixed($action) {
    try {
      if ($action == 'create') {
        $this->load->helper('ip');
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', "\n");
        $this->form_validation->set_rules('subnet_address', '', 'required');
        $this->form_validation->set_rules('ip', 'IP address', 'required|trim|valid_ip');
        $this->form_validation->set_rules('mac', 'MAC address', 'required|trim');
        $this->form_validation->set_rules('name', 'Name', 'required|trim|alpha_dash');
        if (!$this->form_validation->run())
          return jsonize_error(validation_errors());
        $s = $this->doctrine->em->createQuery(
          'SELECT s FROM models\Subnet s WHERE s.address = ?1'
        )->setParameter(1, $this->input->post('subnet_address'))
        ->getSingleResult();
        $addr = '0' . ipint2bits(ip2int($this->input->post('ip'), FALSE),
        FALSE);
        if (substr($s->getAddress(), 0, strlen($s->getAddress()) - 1) !==
        substr($addr, 0, strlen($s->getAddress()) - 1))
          return jsonize_error("The static host IP must be within the subnet");
        $h = new models\FixedHost;
        $h->setIP($addr);
        $h->setMAC($this->input->post('mac'));
        $h->setName($this->input->post('name'));
        $this->doctrine->em->persist($h);
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        return jsonize_success(array());
      } else if ($action == 'update') {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', "\n");
        $this->form_validation->set_rules('ip', '', 'required');
        $this->form_validation->set_rules('mac', 'MAC address', 'required|trim');
        $this->form_validation->set_rules('name', 'Name', 'required|trim|alpha_dash');
        if (!$this->form_validation->run())
          return jsonize_error(validation_errors());
        $h = $this->doctrine->em->createQuery(
          'SELECT h FROM models\FixedHost h WHERE h.ip = ?1'
        )->setParameter(1, $this->input->post('ip'))
        ->getSingleResult();
        $h->setMAC($this->input->post('mac'));
        $h->setName($this->input->post('name'));
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        return jsonize_success(array());
      } else if ($action == 'delete') {
        $host = $this->doctrine->em->createQuery(
          'SELECT h FROM models\FixedHost h WHERE h.ip = ?1'
        )->setParameter(1, $this->input->post('ip'))
        ->getSingleResult();
        $this->doctrine->em->remove($host);
        $this->doctrine->em->flush();
        $this->set_db_changed(TRUE);
        return jsonize_success(array());
      } else
        return jsonize_error("Invalid action: " . $action);
    } catch (Exception $e) {
      return jsonize_error($e->getMessage());
    }
  }

  function history($searchby) {
    parse_str($_SERVER['QUERY_STRING'], $_GET);
    try {
      $this->load->helper('ip');
      $results = array();
      if ($searchby === 'ip') {
        $addr = '0' . ipint2bits(ip2int($_GET['term'], FALSE), FALSE);
        $results = $this->doctrine->em->createQuery(
          'SELECT l FROM models\Lease l WHERE l.ip = ?1 ORDER BY l.begin DESC'
        )->setParameter(1, $addr)
        ->getResult(Query::HYDRATE_ARRAY);
      } else if ($searchby === 'mac') {
        $results = $this->doctrine->em->createQuery(
          'SELECT l FROM models\Lease l WHERE l.mac LIKE ?1 ORDER BY l.begin DESC'
        )->setParameter(1, '%' . $_GET['term'] . '%')
        ->getResult(Query::HYDRATE_ARRAY);
      } else if ($searchby === 'hostname') {
        $results = $this->doctrine->em->createQuery(
          'SELECT l FROM models\Lease l WHERE l.hostname LIKE ?1 ORDER BY l.begin DESC'
        )->setParameter(1, '%' . $_GET['term'] . '%')
        ->getResult(Query::HYDRATE_ARRAY);
      } else
        return jsonize_error("Invalid search type");
      foreach ($results as &$cl) {
        $is_ipv6 = (boolean)($cl['ip'][0] == '1');
        $l = bitcard2addrmask(substr($cl['ip'], 1), $is_ipv6);
        $cl['ip'] = int2ip($l[0], $is_ipv6);
        $cl['begin'] = $cl['begin']->format('Y-m-d H:i:s');
        $cl['end'] = $cl['end']->format('Y-m-d H:i:s');
        $cl['last_renewal'] = $cl['last_renewal']->format('Y-m-d H:i:s');
        $clfinal[] = $cl;
      }
      echo json_encode(array(
        'identifier' => 'begin',
        'label' => 'ip',
        'items' => $results
      ));
      return;
    } catch (Exception $e) {
      return jsonize_error($e->getMessage());
    }
  }

  function config($action) {
    try {
      if ($action == 'commit') {
        $cmd = "/usr/bin/php application/commit_changes.php &";
        $child = popen($cmd, 'r');
        pclose($child);
        return jsonize_success(array());
      } else
        return jsonize_error("Invalid action: " . $action);
    } catch (Exception $e) {
      return jsonize_error($e->getMessage());
    }
  }

  function set_db_changed($changed) {
    $this->load->helper('http');
    $this->doctrine->em->createQuery('UPDATE models\DbChanged c SET c.changed = ?1')
    ->setParameter(1, $changed)
    ->execute();
    http2_post_fields("http://rt.dhcp.secom.net:8003/", array(
      'channel_name' => '/base',
      'payload' => json_encode(array('dbchanged' => TRUE))
    ));
  }
}
