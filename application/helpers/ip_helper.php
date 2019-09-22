<?php

// ip2int
// Convert a presentation IPv4 (xxx.xxx.xxx.xxx) or IPv6 (xxxx:xxxx:...) address
// to an integer (if IPv4) or an array of 4 integers (if IPv6). The IPv6 array
// is big-endian, i.e., the first integer contains the most-significant
// (highest) bits and the 4th contains the least-significant (lowest).
function ip2int($ip, $is_ipv6) {
  // An IPv6 address
  if ($is_ipv6) {
    // Split the string by double-colons and single colons -- i.e., a double-
    // colon counts as a single delimiter -- and return the delimiters along
    // with the quads.
    $exploded = preg_split("/(::|:)/", $ip, -1, PREG_SPLIT_DELIM_CAPTURE);
    $i = count($exploded) - 1;
    // An IPv6 address may have at most 8 quads, which would give us at most
    // 15 elements in the exploded array ($i is 15 - 1 = 14)
    if ($i > 14)
      return FALSE;
    $quads = array(0, 0, 0, 0, 0, 0, 0, 0);
    // We start at the right end of the IPv6 string, which is the 8th quad (idx
    // 7)
    $quad_at = 7;
    $doubledot = FALSE; //We set this to true if we encounter a double-colon
    while ($i >= 0) {
      if ($exploded[$i] == '') {
        // An empty element is only valid at the beginning or end of the array,
        // and only if it is followed by the double-colon (if it is at the
        // beginning) or preceded by it (if it is at the end)
        if ($i != 0 && $i != count($exploded) - 1)
          return FALSE;
        if ($i == 0 && $exploded[1] != '::')
          return FALSE;
        if ($exploded[count($exploded) - 2] != '::')
          return FALSE;
      } else if ($exploded[$i] == '::') {
        // An IPv6 address may contain at most one double-colon. If we've
        // already seen one, the address is invalid.
        if ($doubledot)
          return FALSE;
        $doubledot = TRUE;
        // A double-colon means enough zeros to make the address 128 bits. The
        // next quad we work with is indicated by the number of unscanned
        // elements remaining ($i), minus delimiters (($i + 1) / 2), minus 1
        // since we are done with the current element.
        $quad_at = (($i + 1) / 2) - 1;
      } else if ($exploded[$i] != ':') {
        // Anything other than a single colon means an actual quad. Verify that
        // it consists of at most 4 hexadecimal characters.
        if (preg_match("/^[a-fA-F0-9]{1,4}$/", $exploded[$i]) == 0)
          return FALSE;
		// Convert to decimal and decrement quad idx
        $quads[$quad_at] = hexdec($exploded[$i]);
        $quad_at--;
      }
      $i--;
    }
    // If we have not processed all 8 quads, the address was too short and is
    // invalid.
    if ($quad_at != -1)
      return FALSE;
    // Pack the 8 quads into 4 integers
    return array(
     ($quads[0] << 16) | $quads[1],
     ($quads[2] << 16) | $quads[3],
     ($quads[4] << 16) | $quads[5],
     ($quads[6] << 16) | $quads[7]
     );
  // An IPv4 address
  } else
    return ip2long($ip);
}

// int2ip
// Convert an integer (IPv4 address) or an array of 4 integers (IPv6 address) to
// a presentation address (xxx.xxx.xxx.xxx IPv4, xxxx:xxxx:... IPv6). The IPv6
// array is interpreted big-endian, i.e., the first integer contains the most-
// significant (highest) bits and the 4th contains the least-significant
// (lowest).
function int2ip($num, $is_ipv6) {
  // An IPv6 address
  if ($is_ipv6) {
    // The array must contain exactly 4 integers
    if (count($num) != 4)
      return FALSE;
    $doubledot = FALSE; //We set this to true if we compress 1 or more all-zero quads
    // Unpack the 4 integers into 8 quads. We have to mask the high-order
    // shifted bits because PHP right-shifting preserves the sign bit.
    $quads = array(
     dechex(($num[0] >> 16) & 0xFFFF), dechex($num[0] & 0xFFFF),
     dechex(($num[1] >> 16) & 0xFFFF), dechex($num[1] & 0xFFFF),
     dechex(($num[2] >> 16) & 0xFFFF), dechex($num[2] & 0xFFFF),
     dechex(($num[3] >> 16) & 0xFFFF), dechex($num[3] & 0xFFFF)
     );
	// Start with the first (most-significant) quad and work towards the least-
	// significant.
    $i = 0;
    $foundzero = FALSE; //We set this to 1 if we encounter an all-zero quad
    // This loop looks for all-zero quads to compress with a double-colon.
    while ($i < count($quads)) {
      if ($quads[$i] == '0') {
        if ($foundzero) {
          // Any all-zero quads that follow the first get deleted from the
          // array, and we skip incrementing the idx.
          array_splice($quads, $i, 1);
          continue;
        }
        // Otherwise, this is the first all-zero quad. We set it to the empty
        // string to create a double-colon when we implode the array later on.
        $quads[$i] = '';
        $foundzero = TRUE;
      } else if ($foundzero) {
        // If this quad is non-zero and we already compressed one or more
        // all-zero quads, our work is done, since an IPv6 address may contain
        // at most one double-colon for compressed zeroes.
        break;
      }
      $i++;
    }
    // If the first or last element is zeroes-compressed, implosion won't create
    // a double-colon, so we have to tack an extra colon in.
    if ($quads[0] == '')
      $quads[0] = ':';
    else if ($quads[count($quads) - 1] == '')
      $quads[count($quads) - 1] = ':';
    // Implode the array to create the presentation IPv6 address.
    return implode(':', $quads);
  // An IPv4 address
  } else
    return long2ip($num);
}

function slash2mask($slash) {
  // If $slash is invalid, assume /32
  if (!is_numeric($slash) || $slash < 0 || $slash > 32)
    $slash = 32;
  // Loop through the masks for a 32-bit integer's individual bits from
  // highest-order (0x80000000) to lowest-order (0x1). Because PHP right-
  // shifting preserves the sign bit, mask it out after the first iteration
  // with 0x7FFFFFFF.
  $ret = 0;
  for ($m = 0x80000000, $i = 0; $i < $slash; $m = ($m >> 1) & 0x7FFFFFFF, $i++)
    $ret |= $m;
  return int2ip($ret, FALSE);
}

// ipint2bits
// Turn an integer (IPv4 address) or an array of 4 integers (IPv6 address) into
// a string of 1's and 0's. If a mask (prefix length) is supplied, convert only
// the prefix bits and tack on a '%' character -- this creates a "bitcard",
// which represents a subnet. Bitcards allow database queries using the SQL LIKE
// operator to determine subnet membership.
function ipint2bits($num, $is_ipv6, $mask = -1) {
  $bitstr = ''; //The bit string we will return
  // An IPv6 address
  if ($is_ipv6) {
    // The array must contain exactly 4 integers
    if (count($num) != 4)
      return FALSE;
    // If a mask is not supplied, assume /128 and no wildcard
    if (!is_numeric($mask) || $mask == -1 || $mask > 128)
      $mask = 128;
    // Loop through the array of integers
    foreach ($num as $n) {
      // If we have reached the desired prefix length, exit the outer loop
      if (strlen($bitstr) == $mask)
        break;
      // Loop through the masks for a 32-bit integer's individual bits from
      // highest-order (0x80000000) to lowest-order (0x1). Because PHP right-
      // shifting preserves the sign bit, mask it out after the first iteration
      // with 0x7FFFFFFF.
      for ($i = 0x80000000; $i != 0; $i = ($i >> 1) & 0x7FFFFFFF) {
        // If we have reached the desired prefix length, exit the inner loop
        if (strlen($bitstr) == $mask)
          break;
        // Append a 0 or a 1 to the string based on the current integer bit
        $bitstr .= (($n & $i) ? '1' : '0');
      }
    }
    // If a mask was supplied, tack on the wildcard
    if ($mask < 128)
      $bitstr .= '%';
  // An IPv4 address
  } else {
    // If a mask is not supplied, assume /32 and no wildcard
    if (!is_numeric($mask) || $mask == -1 || $mask > 32)
      $mask = 32;
    // Loop through the masks for a 32-bit integer's individual bits from
    // highest-order (0x80000000) to lowest-order (0x1). Because PHP right-
    // shifting preserves the sign bit, mask it out after the first iteration
    // with 0x7FFFFFFF.
    for ($i = 0x80000000; $i != 0; $i = ($i >> 1) & 0x7FFFFFFF) {
      // If we have reached the desired prefix length, exit the loop
      if (strlen($bitstr) == $mask)
        break;
      // Append a 0 or a 1 to the string based on the current integer bit
      $bitstr .= (($num & $i) ? '1' : '0');
    }
    // If a mask was supplied, tack on the wildcard
    if ($mask < 32)
      $bitstr .= '%';
  }
  return $bitstr;
}

// bitcard2addrmask
// Convert a bitcard (string of 1's and 0's plus wildcard character ('%')) to an
// array consisting of an integer-form IP address (1 integer for IPv4, an inner
// array of 4 integers for IPv6) and an integer prefix length.
function bitcard2addrmask($bitcard, $is_ipv6) {
  $addrmask = array(null, 0); // The return array
  // An IPv6 address
  if ($is_ipv6) {
    // May contain at most 128 bits
    if (strlen($bitcard) > 128)
      return FALSE;
    // The inner array of integers
    $addrmask[0] = array(0, 0, 0, 0);
    // Loop through the bitcard from most-significant to least-significant (left
    // to right in the string), converting '1' and '0' chars to bits in the
    // integers
    for ($i = 0; $i < strlen($bitcard); $i++) {
      if ($bitcard[$i] == '%') // A percent sign is the end of the bitcard
        break;
      $addrmask[1]++;
      // We began with all 0 bits in the integers. If we see a '1' char in the
      // bitcard, set the corresponding bit in the corresponding integer. Since
      // $i is our bit counter from most-significant to least-significant, the
      // integer to set is determined by ($i / 32), and the idx of the bit
      // within the integer by (31 - ($i % 32)).
      if ($bitcard[$i] == '1')
        $addrmask[0][(integer)($i / 32)] |= (1 << (31 - ($i % 32)));
    }
  // An IPv4 address
  } else {
    // May contain at most 32 bits
    if (strlen($bitcard) > 32)
      return FALSE;
    // The IP integer
    $addrmask[0] = 0;
    // Loop through the bitcard from most-significant to least-significant (left
    // to right in the string), converting '1' and '0' chars to bits in the
    // integer
    for ($i = 0; $i < strlen($bitcard); $i++) {
      if ($bitcard[$i] == '%') //A percent sign is the end of the bitcard
        break;
      $addrmask[1]++;
      // We begin with all 0 bits in the integer. If we see a '1' char in the
      // bitcard, set the corresponding bit. Since $i is our bit counter from
      // most-significant to least-significant, the idx of the bit to set within
      // the integer is determined by (31 - $i).
      if ($bitcard[$i] == '1')
        $addrmask[0] |= (1 << (31 - $i));
    }
  }
  return $addrmask;
}
