(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else if(typeof define === 'function' && define.amd)
		define(["CoreHome", , "CorePluginsAdmin"], factory);
	else if(typeof exports === 'object')
		exports["AIProviders"] = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else
		root["AIProviders"] = factory(root["CoreHome"], root["Vue"], root["CorePluginsAdmin"]);
})((typeof self !== 'undefined' ? self : this), function(__WEBPACK_EXTERNAL_MODULE__19dc__, __WEBPACK_EXTERNAL_MODULE__8bbf__, __WEBPACK_EXTERNAL_MODULE_a5a2__) {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "plugins/AIProviders/vue/dist/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "fae3");
/******/ })
/************************************************************************/
/******/ ({

/***/ "19dc":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__19dc__;

/***/ }),

/***/ "8bbf":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__8bbf__;

/***/ }),

/***/ "a5a2":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE_a5a2__;

/***/ }),

/***/ "fae3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "ManageAIProviders", function() { return /* reexport */ ManageAIProviders; });

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/setPublicPath.js
// This file is imported into lib/wc client bundles.

if (typeof window !== 'undefined') {
  var currentScript = window.document.currentScript
  if (false) { var getCurrentScript; }

  var src = currentScript && currentScript.src.match(/(.+\/)[^/]+\.js(\?.*)?$/)
  if (src) {
    __webpack_require__.p = src[1] // eslint-disable-line
  }
}

// Indicate to webpack that this file can be concatenated
/* harmony default export */ var setPublicPath = (null);

// EXTERNAL MODULE: external {"commonjs":"vue","commonjs2":"vue","root":"Vue"}
var external_commonjs_vue_commonjs2_vue_root_Vue_ = __webpack_require__("8bbf");

// EXTERNAL MODULE: external "CoreHome"
var external_CoreHome_ = __webpack_require__("19dc");

// EXTERNAL MODULE: external "CorePluginsAdmin"
var external_CorePluginsAdmin_ = __webpack_require__("a5a2");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/AIProviders/vue/src/ManageAIProviders.vue?vue&type=script&setup=true&lang=ts


const _hoisted_1 = {
  key: 1
};
const _hoisted_2 = {
  key: 1
};
const _hoisted_3 = {
  class: "form-description"
};



/* harmony default export */ var ManageAIProvidersvue_type_script_setup_true_lang_ts = (/*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  __name: 'ManageAIProviders',
  setup(__props) {
    const settings = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(null);
    const isLoading = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const isSaving = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])(false);
    const defaultProviderId = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const defaultCapabilityLevel = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])('');
    const providerConfigurations = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["ref"])({});
    const providerOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      var _settings$value;
      const options = {};
      const providers = ((_settings$value = settings.value) === null || _settings$value === void 0 ? void 0 : _settings$value.providers) || [];
      providers.forEach(provider => {
        options[provider.id] = provider.name;
      });
      return options;
    });
    const capabilityLevelOptions = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["computed"])(() => {
      var _settings$value2;
      const options = {};
      Object.entries(((_settings$value2 = settings.value) === null || _settings$value2 === void 0 ? void 0 : _settings$value2.capabilityLevels) || {}).forEach(([id, translationKey]) => {
        options[id] = Object(external_CoreHome_["translate"])(translationKey);
      });
      return options;
    });
    function applySettings(nextSettings) {
      settings.value = nextSettings;
      defaultProviderId.value = nextSettings.defaultProviderId;
      defaultCapabilityLevel.value = nextSettings.defaultCapabilityLevel;
      const nextProviderConfigurations = {};
      nextSettings.providers.forEach(provider => {
        nextProviderConfigurations[provider.id] = {
          apiKey: '',
          endpointUrl: provider.configuration.endpointUrl || ''
        };
      });
      providerConfigurations.value = nextProviderConfigurations;
    }
    async function loadSettings() {
      isLoading.value = true;
      try {
        const response = await external_CoreHome_["AjaxHelper"].fetch({
          method: 'AIProviders.getSettings'
        });
        applySettings(response);
      } finally {
        isLoading.value = false;
      }
    }
    function updateApiKey(providerId, apiKey) {
      providerConfigurations.value[providerId] = Object.assign(Object.assign({}, providerConfigurations.value[providerId]), {}, {
        apiKey
      });
    }
    function updateEndpointUrl(providerId, endpointUrl) {
      providerConfigurations.value[providerId] = Object.assign(Object.assign({}, providerConfigurations.value[providerId]), {}, {
        endpointUrl
      });
    }
    async function saveSettings() {
      isSaving.value = true;
      try {
        const response = await external_CoreHome_["AjaxHelper"].post({
          method: 'AIProviders.saveSettings'
        }, {
          defaultProviderId: defaultProviderId.value,
          defaultCapabilityLevel: defaultCapabilityLevel.value,
          providerConfigurations: JSON.stringify(providerConfigurations.value)
        }, {
          withTokenInUrl: true
        });
        applySettings(response);
        const notificationInstanceId = external_CoreHome_["NotificationsStore"].show({
          message: Object(external_CoreHome_["translate"])('AIProviders_SettingsSaveSuccess'),
          type: 'transient',
          id: 'aiProvidersSettings',
          context: 'success'
        });
        external_CoreHome_["NotificationsStore"].scrollToNotification(notificationInstanceId);
      } finally {
        isSaving.value = false;
      }
    }
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["onMounted"])(loadSettings);
    return (_ctx, _cache) => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["ContentBlock"]), {
        "content-title": Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_MenuTitle')
      }, {
        default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [isLoading.value ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["ActivityIndicator"]), {
          key: 0,
          loading: isLoading.value
        }, null, 8, ["loading"])) : settings.value ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CorePluginsAdmin_["Field"]), {
          uicontrol: "select",
          name: "defaultProviderId",
          modelValue: defaultProviderId.value,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => defaultProviderId.value = $event),
          title: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_DefaultProvider'),
          options: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(providerOptions)
        }, null, 8, ["modelValue", "title", "options"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CorePluginsAdmin_["Field"]), {
          uicontrol: "select",
          name: "defaultCapabilityLevel",
          modelValue: defaultCapabilityLevel.value,
          "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => defaultCapabilityLevel.value = $event),
          title: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_DefaultCapabilityLevel'),
          options: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(capabilityLevelOptions),
          disabled: !settings.value.canEditCapabilityLevel
        }, null, 8, ["modelValue", "title", "options", "disabled"]), !settings.value.canEditProviderConfiguration ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["Alert"]), {
          key: 0,
          severity: "info"
        }, {
          default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_CloudConfigurationHelp')), 1)]),
          _: 1
        })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), settings.value.canEditProviderConfiguration ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_ProviderConnections')), 1), (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(settings.value.providers, provider => {
          var _providerConfiguratio, _providerConfiguratio2;
          return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
            key: provider.id,
            class: "ai-provider"
          }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h4", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(provider.name), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", _hoisted_3, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])(provider.description)), 1), provider.supportsCustomEndpoint ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CorePluginsAdmin_["Field"]), {
            key: 0,
            uicontrol: "text",
            name: `endpointUrl-${provider.id}`,
            "model-value": (_providerConfiguratio = providerConfigurations.value[provider.id]) === null || _providerConfiguratio === void 0 ? void 0 : _providerConfiguratio.endpointUrl,
            "onUpdate:modelValue": $event => updateEndpointUrl(provider.id, `${$event}`),
            title: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_EndpointUrl'),
            "inline-help": Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_EndpointUrlHelp'),
            autocomplete: "off"
          }, null, 8, ["name", "model-value", "onUpdate:modelValue", "title", "inline-help"])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CorePluginsAdmin_["Field"]), {
            uicontrol: "password",
            name: `apiKey-${provider.id}`,
            "model-value": (_providerConfiguratio2 = providerConfigurations.value[provider.id]) === null || _providerConfiguratio2 === void 0 ? void 0 : _providerConfiguratio2.apiKey,
            "onUpdate:modelValue": $event => updateApiKey(provider.id, `${$event}`),
            title: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_ApiKey'),
            "inline-help": provider.configuration.hasApiKey ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_ApiKeyAlreadyConfigured') : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["translate"])('AIProviders_ApiKeyHelp'),
            autocomplete: "off"
          }, null, 8, ["name", "model-value", "onUpdate:modelValue", "title", "inline-help"]), [[Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CoreHome_["AutoClearPassword"])]])]);
        }), 128))])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CorePluginsAdmin_["SaveButton"]), {
          onConfirm: _cache[2] || (_cache[2] = $event => saveSettings()),
          saving: isSaving.value
        }, null, 8, ["saving"])])), [[Object(external_commonjs_vue_commonjs2_vue_root_Vue_["unref"])(external_CorePluginsAdmin_["Form"])]]) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]),
        _: 1
      }, 8, ["content-title"]);
    };
  }
}));
// CONCATENATED MODULE: ./plugins/AIProviders/vue/src/ManageAIProviders.vue?vue&type=script&setup=true&lang=ts
 
// CONCATENATED MODULE: ./plugins/AIProviders/vue/src/ManageAIProviders.vue



/* harmony default export */ var ManageAIProviders = (ManageAIProvidersvue_type_script_setup_true_lang_ts);
// CONCATENATED MODULE: ./plugins/AIProviders/vue/src/index.ts
/*!
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE:  All information contained herein is, and remains the property of InnoCraft Ltd.
 * The intellectual and technical concepts contained herein are protected by trade secret
 * or copyright law. Redistribution of this information or reproduction of this material is
 * strictly forbidden unless prior written permission is obtained from InnoCraft Ltd.
 *
 * You shall use this code only in accordance with the license agreement obtained from
 * InnoCraft Ltd.
 *
 * @link https://www.innocraft.com/
 * @license For license details see https://www.innocraft.com/license
 */

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/entry-lib-no-default.js




/***/ })

/******/ });
});
//# sourceMappingURL=AIProviders.umd.js.map