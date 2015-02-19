/*
$.extend(Object.prototype, (function() {
	function isUndefined(object) {
		return typeof object === "undefined";
	}
	
	return {
		isUndefined: isUndefined
	}
})());
*/

$.extend(Function.prototype, (function() {
  var _slice = Array.prototype.slice;

  function _update(array, args) {
    var arrayLength = array.length, length = args.length;
    while (length--) array[arrayLength + length] = args[length];
    return array;
  }

  function _merge(array, args) {
    array = _slice.call(array, 0);
    return _update(array, args);
  }

  function bind(context) {
    if (arguments.length < 2 && (typeof arguments[0]  === "undefined")) return this;
    var __method = this, args = _slice.call(arguments, 1);
    return function() {
      var a = _merge(args, arguments);
      return __method.apply(context, a);
    }
  }


  return {
    bind: bind
  }
})());
