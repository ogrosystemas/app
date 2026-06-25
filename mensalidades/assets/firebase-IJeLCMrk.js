var og=Object.defineProperty;var ag=(r,e,t)=>e in r?og(r,e,{enumerable:!0,configurable:!0,writable:!0,value:t}):r[e]=t;var _=(r,e,t)=>ag(r,typeof e!="symbol"?e+"":e,t);const ug=()=>{};var Qh={};/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const v2=function(r){const e=[];let t=0;for(let n=0;n<r.length;n++){let s=r.charCodeAt(n);s<128?e[t++]=s:s<2048?(e[t++]=s>>6|192,e[t++]=s&63|128):(s&64512)===55296&&n+1<r.length&&(r.charCodeAt(n+1)&64512)===56320?(s=65536+((s&1023)<<10)+(r.charCodeAt(++n)&1023),e[t++]=s>>18|240,e[t++]=s>>12&63|128,e[t++]=s>>6&63|128,e[t++]=s&63|128):(e[t++]=s>>12|224,e[t++]=s>>6&63|128,e[t++]=s&63|128)}return e},cg=function(r){const e=[];let t=0,n=0;for(;t<r.length;){const s=r[t++];if(s<128)e[n++]=String.fromCharCode(s);else if(s>191&&s<224){const i=r[t++];e[n++]=String.fromCharCode((s&31)<<6|i&63)}else if(s>239&&s<365){const i=r[t++],o=r[t++],u=r[t++],c=((s&7)<<18|(i&63)<<12|(o&63)<<6|u&63)-65536;e[n++]=String.fromCharCode(55296+(c>>10)),e[n++]=String.fromCharCode(56320+(c&1023))}else{const i=r[t++],o=r[t++];e[n++]=String.fromCharCode((s&15)<<12|(i&63)<<6|o&63)}}return e.join("")},R2={byteToCharMap_:null,charToByteMap_:null,byteToCharMapWebSafe_:null,charToByteMapWebSafe_:null,ENCODED_VALS_BASE:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789",get ENCODED_VALS(){return this.ENCODED_VALS_BASE+"+/="},get ENCODED_VALS_WEBSAFE(){return this.ENCODED_VALS_BASE+"-_."},HAS_NATIVE_SUPPORT:typeof atob=="function",encodeByteArray(r,e){if(!Array.isArray(r))throw Error("encodeByteArray takes an array as a parameter");this.init_();const t=e?this.byteToCharMapWebSafe_:this.byteToCharMap_,n=[];for(let s=0;s<r.length;s+=3){const i=r[s],o=s+1<r.length,u=o?r[s+1]:0,c=s+2<r.length,l=c?r[s+2]:0,d=i>>2,g=(i&3)<<4|u>>4;let y=(u&15)<<2|l>>6,R=l&63;c||(R=64,o||(y=64)),n.push(t[d],t[g],t[y],t[R])}return n.join("")},encodeString(r,e){return this.HAS_NATIVE_SUPPORT&&!e?btoa(r):this.encodeByteArray(v2(r),e)},decodeString(r,e){return this.HAS_NATIVE_SUPPORT&&!e?atob(r):cg(this.decodeStringToByteArray(r,e))},decodeStringToByteArray(r,e){this.init_();const t=e?this.charToByteMapWebSafe_:this.charToByteMap_,n=[];for(let s=0;s<r.length;){const i=t[r.charAt(s++)],u=s<r.length?t[r.charAt(s)]:0;++s;const l=s<r.length?t[r.charAt(s)]:64;++s;const g=s<r.length?t[r.charAt(s)]:64;if(++s,i==null||u==null||l==null||g==null)throw new lg;const y=i<<2|u>>4;if(n.push(y),l!==64){const R=u<<4&240|l>>2;if(n.push(R),g!==64){const C=l<<6&192|g;n.push(C)}}}return n},init_(){if(!this.byteToCharMap_){this.byteToCharMap_={},this.charToByteMap_={},this.byteToCharMapWebSafe_={},this.charToByteMapWebSafe_={};for(let r=0;r<this.ENCODED_VALS.length;r++)this.byteToCharMap_[r]=this.ENCODED_VALS.charAt(r),this.charToByteMap_[this.byteToCharMap_[r]]=r,this.byteToCharMapWebSafe_[r]=this.ENCODED_VALS_WEBSAFE.charAt(r),this.charToByteMapWebSafe_[this.byteToCharMapWebSafe_[r]]=r,r>=this.ENCODED_VALS_BASE.length&&(this.charToByteMap_[this.ENCODED_VALS_WEBSAFE.charAt(r)]=r,this.charToByteMapWebSafe_[this.ENCODED_VALS.charAt(r)]=r)}}};class lg extends Error{constructor(){super(...arguments),this.name="DecodeBase64StringError"}}const hg=function(r){const e=v2(r);return R2.encodeByteArray(e,!0)},S2=function(r){return hg(r).replace(/\./g,"")},P2=function(r){try{return R2.decodeString(r,!0)}catch(e){console.error("base64Decode failed: ",e)}return null};/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function b2(){if(typeof self<"u")return self;if(typeof window<"u")return window;if(typeof global<"u")return global;throw new Error("Unable to locate global object.")}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const dg=()=>b2().__FIREBASE_DEFAULTS__,fg=()=>{if(typeof process>"u"||typeof Qh>"u")return;const r=Qh.__FIREBASE_DEFAULTS__;if(r)return JSON.parse(r)},pg=()=>{if(typeof document>"u")return;let r;try{r=document.cookie.match(/__FIREBASE_DEFAULTS__=([^;]+)/)}catch{return}const e=r&&P2(r[1]);return e&&JSON.parse(e)},Za=()=>{try{return ug()||dg()||fg()||pg()}catch(r){console.info(`Unable to get __FIREBASE_DEFAULTS__ due to: ${r}`);return}},gg=r=>{var e,t;return(t=(e=Za())==null?void 0:e.emulatorHosts)==null?void 0:t[r]},C2=()=>{var r;return(r=Za())==null?void 0:r.config},N2=r=>{var e;return(e=Za())==null?void 0:e[`_${r}`]};/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class mg{constructor(){this.reject=()=>{},this.resolve=()=>{},this.promise=new Promise((e,t)=>{this.resolve=e,this.reject=t})}wrapCallback(e){return(t,n)=>{t?this.reject(t):this.resolve(n),typeof e=="function"&&(this.promise.catch(()=>{}),e.length===1?e(t):e(t,n))}}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function qe(){return typeof navigator<"u"&&typeof navigator.userAgent=="string"?navigator.userAgent:""}function _g(){return typeof window<"u"&&!!(window.cordova||window.phonegap||window.PhoneGap)&&/ios|iphone|ipod|ipad|android|blackberry|iemobile/i.test(qe())}function V2(){var e;const r=(e=Za())==null?void 0:e.forceEnvironment;if(r==="node")return!0;if(r==="browser")return!1;try{return Object.prototype.toString.call(global.process)==="[object process]"}catch{return!1}}function yg(){return typeof navigator<"u"&&navigator.userAgent==="Cloudflare-Workers"}function Eg(){const r=typeof chrome=="object"?chrome.runtime:typeof browser=="object"?browser.runtime:void 0;return typeof r=="object"&&r.id!==void 0}function Ig(){return typeof navigator=="object"&&navigator.product==="ReactNative"}function wg(){const r=qe();return r.indexOf("MSIE ")>=0||r.indexOf("Trident/")>=0}function x2(){return!V2()&&!!navigator.userAgent&&navigator.userAgent.includes("Safari")&&!navigator.userAgent.includes("Chrome")}function D2(){return!V2()&&!!navigator.userAgent&&(navigator.userAgent.includes("Safari")||navigator.userAgent.includes("WebKit"))&&!navigator.userAgent.includes("Chrome")}function h1(){try{return typeof indexedDB=="object"}catch{return!1}}function O2(){return new Promise((r,e)=>{try{let t=!0;const n="validate-browser-context-for-indexeddb-analytics-module",s=self.indexedDB.open(n);s.onsuccess=()=>{s.result.close(),t||self.indexedDB.deleteDatabase(n),r(!0)},s.onupgradeneeded=()=>{t=!1},s.onerror=()=>{var i;e(((i=s.error)==null?void 0:i.message)||"")}}catch(t){e(t)}})}function Tg(){return!(typeof navigator>"u"||!navigator.cookieEnabled)}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Ag="FirebaseError";class en extends Error{constructor(e,t,n){super(t),this.code=e,this.customData=n,this.name=Ag,Object.setPrototypeOf(this,en.prototype),Error.captureStackTrace&&Error.captureStackTrace(this,Br.prototype.create)}}class Br{constructor(e,t,n){this.service=e,this.serviceName=t,this.errors=n}create(e,...t){const n=t[0]||{},s=`${this.service}/${e}`,i=this.errors[e],o=i?vg(i,n):"Error",u=`${this.serviceName}: ${o} (${s}).`;return new en(s,u,n)}}function vg(r,e){return r.replace(Rg,(t,n)=>{const s=e[n];return s!=null?String(s):`<${n}?>`})}const Rg=/\{\$([^}]+)}/g;function Sg(r){for(const e in r)if(Object.prototype.hasOwnProperty.call(r,e))return!1;return!0}function Nr(r,e){if(r===e)return!0;const t=Object.keys(r),n=Object.keys(e);for(const s of t){if(!n.includes(s))return!1;const i=r[s],o=e[s];if(Yh(i)&&Yh(o)){if(!Nr(i,o))return!1}else if(i!==o)return!1}for(const s of n)if(!t.includes(s))return!1;return!0}function Yh(r){return r!==null&&typeof r=="object"}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function lo(r){const e=[];for(const[t,n]of Object.entries(r))Array.isArray(n)?n.forEach(s=>{e.push(encodeURIComponent(t)+"="+encodeURIComponent(s))}):e.push(encodeURIComponent(t)+"="+encodeURIComponent(n));return e.length?"&"+e.join("&"):""}function Pg(r,e){const t=new bg(r,e);return t.subscribe.bind(t)}class bg{constructor(e,t){this.observers=[],this.unsubscribes=[],this.observerCount=0,this.task=Promise.resolve(),this.finalized=!1,this.onNoObservers=t,this.task.then(()=>{e(this)}).catch(n=>{this.error(n)})}next(e){this.forEachObserver(t=>{t.next(e)})}error(e){this.forEachObserver(t=>{t.error(e)}),this.close(e)}complete(){this.forEachObserver(e=>{e.complete()}),this.close()}subscribe(e,t,n){let s;if(e===void 0&&t===void 0&&n===void 0)throw new Error("Missing Observer.");Cg(e,["next","error","complete"])?s=e:s={next:e,error:t,complete:n},s.next===void 0&&(s.next=rc),s.error===void 0&&(s.error=rc),s.complete===void 0&&(s.complete=rc);const i=this.unsubscribeOne.bind(this,this.observers.length);return this.finalized&&this.task.then(()=>{try{this.finalError?s.error(this.finalError):s.complete()}catch{}}),this.observers.push(s),i}unsubscribeOne(e){this.observers===void 0||this.observers[e]===void 0||(delete this.observers[e],this.observerCount-=1,this.observerCount===0&&this.onNoObservers!==void 0&&this.onNoObservers(this))}forEachObserver(e){if(!this.finalized)for(let t=0;t<this.observers.length;t++)this.sendOne(t,e)}sendOne(e,t){this.task.then(()=>{if(this.observers!==void 0&&this.observers[e]!==void 0)try{t(this.observers[e])}catch(n){typeof console<"u"&&console.error&&console.error(n)}})}close(e){this.finalized||(this.finalized=!0,e!==void 0&&(this.finalError=e),this.task.then(()=>{this.observers=void 0,this.onNoObservers=void 0}))}}function Cg(r,e){if(typeof r!="object"||r===null)return!1;for(const t of e)if(t in r&&typeof r[t]=="function")return!0;return!1}function rc(){}/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function be(r){return r&&r._delegate?r._delegate:r}/**
 * @license
 * Copyright 2025 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function ho(r){try{return(r.startsWith("http://")||r.startsWith("https://")?new URL(r).hostname:r).endsWith(".cloudworkstations.dev")}catch{return!1}}async function k2(r){return(await fetch(r,{credentials:"include"})).ok}class kt{constructor(e,t,n){this.name=e,this.instanceFactory=t,this.type=n,this.multipleInstances=!1,this.serviceProps={},this.instantiationMode="LAZY",this.onInstanceCreated=null}setInstantiationMode(e){return this.instantiationMode=e,this}setMultipleInstances(e){return this.multipleInstances=e,this}setServiceProps(e){return this.serviceProps=e,this}setInstanceCreatedCallback(e){return this.onInstanceCreated=e,this}}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const dr="[DEFAULT]";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Ng{constructor(e,t){this.name=e,this.container=t,this.component=null,this.instances=new Map,this.instancesDeferred=new Map,this.instancesOptions=new Map,this.onInitCallbacks=new Map}get(e){const t=this.normalizeInstanceIdentifier(e);if(!this.instancesDeferred.has(t)){const n=new mg;if(this.instancesDeferred.set(t,n),this.isInitialized(t)||this.shouldAutoInitialize())try{const s=this.getOrInitializeService({instanceIdentifier:t});s&&n.resolve(s)}catch{}}return this.instancesDeferred.get(t).promise}getImmediate(e){const t=this.normalizeInstanceIdentifier(e==null?void 0:e.identifier),n=(e==null?void 0:e.optional)??!1;if(this.isInitialized(t)||this.shouldAutoInitialize())try{return this.getOrInitializeService({instanceIdentifier:t})}catch(s){if(n)return null;throw s}else{if(n)return null;throw Error(`Service ${this.name} is not available`)}}getComponent(){return this.component}setComponent(e){if(e.name!==this.name)throw Error(`Mismatching Component ${e.name} for Provider ${this.name}.`);if(this.component)throw Error(`Component for ${this.name} has already been provided`);if(this.component=e,!!this.shouldAutoInitialize()){if(xg(e))try{this.getOrInitializeService({instanceIdentifier:dr})}catch{}for(const[t,n]of this.instancesDeferred.entries()){const s=this.normalizeInstanceIdentifier(t);try{const i=this.getOrInitializeService({instanceIdentifier:s});n.resolve(i)}catch{}}}}clearInstance(e=dr){this.instancesDeferred.delete(e),this.instancesOptions.delete(e),this.instances.delete(e)}async delete(){const e=Array.from(this.instances.values());await Promise.all([...e.filter(t=>"INTERNAL"in t).map(t=>t.INTERNAL.delete()),...e.filter(t=>"_delete"in t).map(t=>t._delete())])}isComponentSet(){return this.component!=null}isInitialized(e=dr){return this.instances.has(e)}getOptions(e=dr){return this.instancesOptions.get(e)||{}}initialize(e={}){const{options:t={}}=e,n=this.normalizeInstanceIdentifier(e.instanceIdentifier);if(this.isInitialized(n))throw Error(`${this.name}(${n}) has already been initialized`);if(!this.isComponentSet())throw Error(`Component ${this.name} has not been registered yet`);const s=this.getOrInitializeService({instanceIdentifier:n,options:t});for(const[i,o]of this.instancesDeferred.entries()){const u=this.normalizeInstanceIdentifier(i);n===u&&o.resolve(s)}return s}onInit(e,t){const n=this.normalizeInstanceIdentifier(t),s=this.onInitCallbacks.get(n)??new Set;s.add(e),this.onInitCallbacks.set(n,s);const i=this.instances.get(n);return i&&e(i,n),()=>{s.delete(e)}}invokeOnInitCallbacks(e,t){const n=this.onInitCallbacks.get(t);if(n)for(const s of n)try{s(e,t)}catch{}}getOrInitializeService({instanceIdentifier:e,options:t={}}){let n=this.instances.get(e);if(!n&&this.component&&(n=this.component.instanceFactory(this.container,{instanceIdentifier:Vg(e),options:t}),this.instances.set(e,n),this.instancesOptions.set(e,t),this.invokeOnInitCallbacks(n,e),this.component.onInstanceCreated))try{this.component.onInstanceCreated(this.container,e,n)}catch{}return n||null}normalizeInstanceIdentifier(e=dr){return this.component?this.component.multipleInstances?e:dr:e}shouldAutoInitialize(){return!!this.component&&this.component.instantiationMode!=="EXPLICIT"}}function Vg(r){return r===dr?void 0:r}function xg(r){return r.instantiationMode==="EAGER"}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Dg{constructor(e){this.name=e,this.providers=new Map}addComponent(e){const t=this.getProvider(e.name);if(t.isComponentSet())throw new Error(`Component ${e.name} has already been registered with ${this.name}`);t.setComponent(e)}addOrOverwriteComponent(e){this.getProvider(e.name).isComponentSet()&&this.providers.delete(e.name),this.addComponent(e)}getProvider(e){if(this.providers.has(e))return this.providers.get(e);const t=new Ng(e,this);return this.providers.set(e,t),t}getProviders(){return Array.from(this.providers.values())}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */var ue;(function(r){r[r.DEBUG=0]="DEBUG",r[r.VERBOSE=1]="VERBOSE",r[r.INFO=2]="INFO",r[r.WARN=3]="WARN",r[r.ERROR=4]="ERROR",r[r.SILENT=5]="SILENT"})(ue||(ue={}));const Og={debug:ue.DEBUG,verbose:ue.VERBOSE,info:ue.INFO,warn:ue.WARN,error:ue.ERROR,silent:ue.SILENT},kg=ue.INFO,Lg={[ue.DEBUG]:"log",[ue.VERBOSE]:"log",[ue.INFO]:"info",[ue.WARN]:"warn",[ue.ERROR]:"error"},Mg=(r,e,...t)=>{if(e<r.logLevel)return;const n=new Date().toISOString(),s=Lg[e];if(s)console[s](`[${n}]  ${r.name}:`,...t);else throw new Error(`Attempted to log a message with an invalid logType (value: ${e})`)};class d1{constructor(e){this.name=e,this._logLevel=kg,this._logHandler=Mg,this._userLogHandler=null}get logLevel(){return this._logLevel}set logLevel(e){if(!(e in ue))throw new TypeError(`Invalid value "${e}" assigned to \`logLevel\``);this._logLevel=e}setLogLevel(e){this._logLevel=typeof e=="string"?Og[e]:e}get logHandler(){return this._logHandler}set logHandler(e){if(typeof e!="function")throw new TypeError("Value assigned to `logHandler` must be a function");this._logHandler=e}get userLogHandler(){return this._userLogHandler}set userLogHandler(e){this._userLogHandler=e}debug(...e){this._userLogHandler&&this._userLogHandler(this,ue.DEBUG,...e),this._logHandler(this,ue.DEBUG,...e)}log(...e){this._userLogHandler&&this._userLogHandler(this,ue.VERBOSE,...e),this._logHandler(this,ue.VERBOSE,...e)}info(...e){this._userLogHandler&&this._userLogHandler(this,ue.INFO,...e),this._logHandler(this,ue.INFO,...e)}warn(...e){this._userLogHandler&&this._userLogHandler(this,ue.WARN,...e),this._logHandler(this,ue.WARN,...e)}error(...e){this._userLogHandler&&this._userLogHandler(this,ue.ERROR,...e),this._logHandler(this,ue.ERROR,...e)}}const Fg=(r,e)=>e.some(t=>r instanceof t);let Xh,Jh;function Ug(){return Xh||(Xh=[IDBDatabase,IDBObjectStore,IDBIndex,IDBCursor,IDBTransaction])}function Bg(){return Jh||(Jh=[IDBCursor.prototype.advance,IDBCursor.prototype.continue,IDBCursor.prototype.continuePrimaryKey])}const L2=new WeakMap,vc=new WeakMap,M2=new WeakMap,sc=new WeakMap,f1=new WeakMap;function qg(r){const e=new Promise((t,n)=>{const s=()=>{r.removeEventListener("success",i),r.removeEventListener("error",o)},i=()=>{t(on(r.result)),s()},o=()=>{n(r.error),s()};r.addEventListener("success",i),r.addEventListener("error",o)});return e.then(t=>{t instanceof IDBCursor&&L2.set(t,r)}).catch(()=>{}),f1.set(e,r),e}function $g(r){if(vc.has(r))return;const e=new Promise((t,n)=>{const s=()=>{r.removeEventListener("complete",i),r.removeEventListener("error",o),r.removeEventListener("abort",o)},i=()=>{t(),s()},o=()=>{n(r.error||new DOMException("AbortError","AbortError")),s()};r.addEventListener("complete",i),r.addEventListener("error",o),r.addEventListener("abort",o)});vc.set(r,e)}let Rc={get(r,e,t){if(r instanceof IDBTransaction){if(e==="done")return vc.get(r);if(e==="objectStoreNames")return r.objectStoreNames||M2.get(r);if(e==="store")return t.objectStoreNames[1]?void 0:t.objectStore(t.objectStoreNames[0])}return on(r[e])},set(r,e,t){return r[e]=t,!0},has(r,e){return r instanceof IDBTransaction&&(e==="done"||e==="store")?!0:e in r}};function Gg(r){Rc=r(Rc)}function jg(r){return r===IDBDatabase.prototype.transaction&&!("objectStoreNames"in IDBTransaction.prototype)?function(e,...t){const n=r.call(ic(this),e,...t);return M2.set(n,e.sort?e.sort():[e]),on(n)}:Bg().includes(r)?function(...e){return r.apply(ic(this),e),on(L2.get(this))}:function(...e){return on(r.apply(ic(this),e))}}function zg(r){return typeof r=="function"?jg(r):(r instanceof IDBTransaction&&$g(r),Fg(r,Ug())?new Proxy(r,Rc):r)}function on(r){if(r instanceof IDBRequest)return qg(r);if(sc.has(r))return sc.get(r);const e=zg(r);return e!==r&&(sc.set(r,e),f1.set(e,r)),e}const ic=r=>f1.get(r);function eu(r,e,{blocked:t,upgrade:n,blocking:s,terminated:i}={}){const o=indexedDB.open(r,e),u=on(o);return n&&o.addEventListener("upgradeneeded",c=>{n(on(o.result),c.oldVersion,c.newVersion,on(o.transaction),c)}),t&&o.addEventListener("blocked",c=>t(c.oldVersion,c.newVersion,c)),u.then(c=>{i&&c.addEventListener("close",()=>i()),s&&c.addEventListener("versionchange",l=>s(l.oldVersion,l.newVersion,l))}).catch(()=>{}),u}function ia(r,{blocked:e}={}){const t=indexedDB.deleteDatabase(r);return e&&t.addEventListener("blocked",n=>e(n.oldVersion,n)),on(t).then(()=>{})}const Hg=["get","getKey","getAll","getAllKeys","count"],Kg=["put","add","delete","clear"],oc=new Map;function Zh(r,e){if(!(r instanceof IDBDatabase&&!(e in r)&&typeof e=="string"))return;if(oc.get(e))return oc.get(e);const t=e.replace(/FromIndex$/,""),n=e!==t,s=Kg.includes(t);if(!(t in(n?IDBIndex:IDBObjectStore).prototype)||!(s||Hg.includes(t)))return;const i=async function(o,...u){const c=this.transaction(o,s?"readwrite":"readonly");let l=c.store;return n&&(l=l.index(u.shift())),(await Promise.all([l[t](...u),s&&c.done]))[0]};return oc.set(e,i),i}Gg(r=>({...r,get:(e,t,n)=>Zh(e,t)||r.get(e,t,n),has:(e,t)=>!!Zh(e,t)||r.has(e,t)}));/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Wg{constructor(e){this.container=e}getPlatformInfoString(){return this.container.getProviders().map(t=>{if(Qg(t)){const n=t.getImmediate();return`${n.library}/${n.version}`}else return null}).filter(t=>t).join(" ")}}function Qg(r){const e=r.getComponent();return(e==null?void 0:e.type)==="VERSION"}const Sc="@firebase/app",ed="0.15.0";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const ln=new d1("@firebase/app"),Yg="@firebase/app-compat",Xg="@firebase/analytics-compat",Jg="@firebase/analytics",Zg="@firebase/app-check-compat",em="@firebase/app-check",tm="@firebase/auth",nm="@firebase/auth-compat",rm="@firebase/database",sm="@firebase/data-connect",im="@firebase/database-compat",om="@firebase/functions",am="@firebase/functions-compat",um="@firebase/installations",cm="@firebase/installations-compat",lm="@firebase/messaging",hm="@firebase/messaging-compat",dm="@firebase/performance",fm="@firebase/performance-compat",pm="@firebase/remote-config",gm="@firebase/remote-config-compat",mm="@firebase/storage",_m="@firebase/storage-compat",ym="@firebase/firestore",Em="@firebase/ai",Im="@firebase/firestore-compat",wm="firebase",Tm="12.15.0";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Pc="[DEFAULT]",Am={[Sc]:"fire-core",[Yg]:"fire-core-compat",[Jg]:"fire-analytics",[Xg]:"fire-analytics-compat",[em]:"fire-app-check",[Zg]:"fire-app-check-compat",[tm]:"fire-auth",[nm]:"fire-auth-compat",[rm]:"fire-rtdb",[sm]:"fire-data-connect",[im]:"fire-rtdb-compat",[om]:"fire-fn",[am]:"fire-fn-compat",[um]:"fire-iid",[cm]:"fire-iid-compat",[lm]:"fire-fcm",[hm]:"fire-fcm-compat",[dm]:"fire-perf",[fm]:"fire-perf-compat",[pm]:"fire-rc",[gm]:"fire-rc-compat",[mm]:"fire-gcs",[_m]:"fire-gcs-compat",[ym]:"fire-fst",[Im]:"fire-fst-compat",[Em]:"fire-vertex","fire-js":"fire-js",[wm]:"fire-js-all"};/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Ta=new Map,vm=new Map,bc=new Map;function td(r,e){try{r.container.addComponent(e)}catch(t){ln.debug(`Component ${e.name} failed to register with FirebaseApp ${r.name}`,t)}}function Yt(r){const e=r.name;if(bc.has(e))return ln.debug(`There were multiple attempts to register component ${e}.`),!1;bc.set(e,r);for(const t of Ta.values())td(t,r);for(const t of vm.values())td(t,r);return!0}function Os(r,e){const t=r.container.getProvider("heartbeat").getImmediate({optional:!0});return t&&t.triggerHeartbeat(),r.container.getProvider(e)}function Nt(r){return r==null?!1:r.settings!==void 0}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Rm={"no-app":"No Firebase App '{$appName}' has been created - call initializeApp() first","bad-app-name":"Illegal App name: '{$appName}'","duplicate-app":"Firebase App named '{$appName}' already exists with different options or config","app-deleted":"Firebase App named '{$appName}' already deleted","server-app-deleted":"Firebase Server App has been deleted","no-options":"Need to provide options, when not being deployed to hosting via source.","invalid-app-argument":"firebase.{$appName}() takes either no argument or a Firebase App instance.","invalid-log-argument":"First argument to `onLog` must be null or a function.","idb-open":"Error thrown when opening IndexedDB. Original error: {$originalErrorMessage}.","idb-get":"Error thrown when reading from IndexedDB. Original error: {$originalErrorMessage}.","idb-set":"Error thrown when writing to IndexedDB. Original error: {$originalErrorMessage}.","idb-delete":"Error thrown when deleting from IndexedDB. Original error: {$originalErrorMessage}.","finalization-registry-not-supported":"FirebaseServerApp deleteOnDeref field defined but the JS runtime does not support FinalizationRegistry.","invalid-server-app-environment":"FirebaseServerApp is not for use in browser environments."},Bn=new Br("app","Firebase",Rm);/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Sm{constructor(e,t,n){this._isDeleted=!1,this._options={...e},this._config={...t},this._name=t.name,this._automaticDataCollectionEnabled=t.automaticDataCollectionEnabled,this._container=n,this.container.addComponent(new kt("app",()=>this,"PUBLIC"))}get automaticDataCollectionEnabled(){return this.checkDestroyed(),this._automaticDataCollectionEnabled}set automaticDataCollectionEnabled(e){this.checkDestroyed(),this._automaticDataCollectionEnabled=e}get name(){return this.checkDestroyed(),this._name}get options(){return this.checkDestroyed(),this._options}get config(){return this.checkDestroyed(),this._config}get container(){return this._container}get isDeleted(){return this._isDeleted}set isDeleted(e){this._isDeleted=e}checkDestroyed(){if(this.isDeleted)throw Bn.create("app-deleted",{appName:this._name})}}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const ks=Tm;function Pm(r,e={}){let t=r;typeof e!="object"&&(e={name:e});const n={name:Pc,automaticDataCollectionEnabled:!0,...e},s=n.name;if(typeof s!="string"||!s)throw Bn.create("bad-app-name",{appName:String(s)});if(t||(t=C2()),!t)throw Bn.create("no-options");const i=Ta.get(s);if(i){if(Nr(t,i.options)&&Nr(n,i.config))return i;throw Bn.create("duplicate-app",{appName:s})}const o=new Dg(s);for(const c of bc.values())o.addComponent(c);const u=new Sm(t,n,o);return Ta.set(s,u),u}function F2(r=Pc){const e=Ta.get(r);if(!e&&r===Pc&&C2())return Pm();if(!e)throw Bn.create("no-app",{appName:r});return e}function Rt(r,e,t){let n=Am[r]??r;t&&(n+=`-${t}`);const s=n.match(/\s|\//),i=e.match(/\s|\//);if(s||i){const o=[`Unable to register library "${n}" with version "${e}":`];s&&o.push(`library name "${n}" contains illegal characters (whitespace or "/")`),s&&i&&o.push("and"),i&&o.push(`version name "${e}" contains illegal characters (whitespace or "/")`),ln.warn(o.join(" "));return}Yt(new kt(`${n}-version`,()=>({library:n,version:e}),"VERSION"))}/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const bm="firebase-heartbeat-database",Cm=1,$i="firebase-heartbeat-store";let ac=null;function U2(){return ac||(ac=eu(bm,Cm,{upgrade:(r,e)=>{switch(e){case 0:try{r.createObjectStore($i)}catch(t){console.warn(t)}}}}).catch(r=>{throw Bn.create("idb-open",{originalErrorMessage:r.message})})),ac}async function Nm(r){try{const t=(await U2()).transaction($i),n=await t.objectStore($i).get(B2(r));return await t.done,n}catch(e){if(e instanceof en)ln.warn(e.message);else{const t=Bn.create("idb-get",{originalErrorMessage:e==null?void 0:e.message});ln.warn(t.message)}}}async function nd(r,e){try{const n=(await U2()).transaction($i,"readwrite");await n.objectStore($i).put(e,B2(r)),await n.done}catch(t){if(t instanceof en)ln.warn(t.message);else{const n=Bn.create("idb-set",{originalErrorMessage:t==null?void 0:t.message});ln.warn(n.message)}}}function B2(r){return`${r.name}!${r.options.appId}`}/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Vm=1024,xm=30;class Dm{constructor(e){this.container=e,this._heartbeatsCache=null;const t=this.container.getProvider("app").getImmediate();this._storage=new km(t),this._heartbeatsCachePromise=this._storage.read().then(n=>(this._heartbeatsCache=n,n))}async triggerHeartbeat(){var e,t;try{const s=this.container.getProvider("platform-logger").getImmediate().getPlatformInfoString(),i=rd();if(((e=this._heartbeatsCache)==null?void 0:e.heartbeats)==null&&(this._heartbeatsCache=await this._heartbeatsCachePromise,((t=this._heartbeatsCache)==null?void 0:t.heartbeats)==null)||this._heartbeatsCache.lastSentHeartbeatDate===i||this._heartbeatsCache.heartbeats.some(o=>o.date===i))return;if(this._heartbeatsCache.heartbeats.push({date:i,agent:s}),this._heartbeatsCache.heartbeats.length>xm){const o=Lm(this._heartbeatsCache.heartbeats);this._heartbeatsCache.heartbeats.splice(o,1)}return this._storage.overwrite(this._heartbeatsCache)}catch(n){ln.warn(n)}}async getHeartbeatsHeader(){var e;try{if(this._heartbeatsCache===null&&await this._heartbeatsCachePromise,((e=this._heartbeatsCache)==null?void 0:e.heartbeats)==null||this._heartbeatsCache.heartbeats.length===0)return"";const t=rd(),{heartbeatsToSend:n,unsentEntries:s}=Om(this._heartbeatsCache.heartbeats),i=S2(JSON.stringify({version:2,heartbeats:n}));return this._heartbeatsCache.lastSentHeartbeatDate=t,s.length>0?(this._heartbeatsCache.heartbeats=s,await this._storage.overwrite(this._heartbeatsCache)):(this._heartbeatsCache.heartbeats=[],this._storage.overwrite(this._heartbeatsCache)),i}catch(t){return ln.warn(t),""}}}function rd(){return new Date().toISOString().substring(0,10)}function Om(r,e=Vm){const t=[];let n=r.slice();for(const s of r){const i=t.find(o=>o.agent===s.agent);if(i){if(i.dates.push(s.date),sd(t)>e){i.dates.pop();break}}else if(t.push({agent:s.agent,dates:[s.date]}),sd(t)>e){t.pop();break}n=n.slice(1)}return{heartbeatsToSend:t,unsentEntries:n}}class km{constructor(e){this.app=e,this._canUseIndexedDBPromise=this.runIndexedDBEnvironmentCheck()}async runIndexedDBEnvironmentCheck(){return h1()?O2().then(()=>!0).catch(()=>!1):!1}async read(){if(await this._canUseIndexedDBPromise){const t=await Nm(this.app);return t!=null&&t.heartbeats?t:{heartbeats:[]}}else return{heartbeats:[]}}async overwrite(e){if(await this._canUseIndexedDBPromise){const n=await this.read();return nd(this.app,{lastSentHeartbeatDate:e.lastSentHeartbeatDate??n.lastSentHeartbeatDate,heartbeats:e.heartbeats})}else return}async add(e){if(await this._canUseIndexedDBPromise){const n=await this.read();return nd(this.app,{lastSentHeartbeatDate:e.lastSentHeartbeatDate??n.lastSentHeartbeatDate,heartbeats:[...n.heartbeats,...e.heartbeats]})}else return}}function sd(r){return S2(JSON.stringify({version:2,heartbeats:r})).length}function Lm(r){if(r.length===0)return-1;let e=0,t=r[0].date;for(let n=1;n<r.length;n++)r[n].date<t&&(t=r[n].date,e=n);return e}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Mm(r){Yt(new kt("platform-logger",e=>new Wg(e),"PRIVATE")),Yt(new kt("heartbeat",e=>new Dm(e),"PRIVATE")),Rt(Sc,ed,r),Rt(Sc,ed,"esm2020"),Rt("fire-js","")}Mm("");var id=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};/** @license
Copyright The Closure Library Authors.
SPDX-License-Identifier: Apache-2.0
*/var qn,q2;(function(){var r;/** @license

 Copyright The Closure Library Authors.
 SPDX-License-Identifier: Apache-2.0
*/function e(A,E){function w(){}w.prototype=E.prototype,A.F=E.prototype,A.prototype=new w,A.prototype.constructor=A,A.D=function(S,v,N){for(var I=Array(arguments.length-2),lt=2;lt<arguments.length;lt++)I[lt-2]=arguments[lt];return E.prototype[v].apply(S,I)}}function t(){this.blockSize=-1}function n(){this.blockSize=-1,this.blockSize=64,this.g=Array(4),this.C=Array(this.blockSize),this.o=this.h=0,this.u()}e(n,t),n.prototype.u=function(){this.g[0]=1732584193,this.g[1]=4023233417,this.g[2]=2562383102,this.g[3]=271733878,this.o=this.h=0};function s(A,E,w){w||(w=0);const S=Array(16);if(typeof E=="string")for(var v=0;v<16;++v)S[v]=E.charCodeAt(w++)|E.charCodeAt(w++)<<8|E.charCodeAt(w++)<<16|E.charCodeAt(w++)<<24;else for(v=0;v<16;++v)S[v]=E[w++]|E[w++]<<8|E[w++]<<16|E[w++]<<24;E=A.g[0],w=A.g[1],v=A.g[2];let N=A.g[3],I;I=E+(N^w&(v^N))+S[0]+3614090360&4294967295,E=w+(I<<7&4294967295|I>>>25),I=N+(v^E&(w^v))+S[1]+3905402710&4294967295,N=E+(I<<12&4294967295|I>>>20),I=v+(w^N&(E^w))+S[2]+606105819&4294967295,v=N+(I<<17&4294967295|I>>>15),I=w+(E^v&(N^E))+S[3]+3250441966&4294967295,w=v+(I<<22&4294967295|I>>>10),I=E+(N^w&(v^N))+S[4]+4118548399&4294967295,E=w+(I<<7&4294967295|I>>>25),I=N+(v^E&(w^v))+S[5]+1200080426&4294967295,N=E+(I<<12&4294967295|I>>>20),I=v+(w^N&(E^w))+S[6]+2821735955&4294967295,v=N+(I<<17&4294967295|I>>>15),I=w+(E^v&(N^E))+S[7]+4249261313&4294967295,w=v+(I<<22&4294967295|I>>>10),I=E+(N^w&(v^N))+S[8]+1770035416&4294967295,E=w+(I<<7&4294967295|I>>>25),I=N+(v^E&(w^v))+S[9]+2336552879&4294967295,N=E+(I<<12&4294967295|I>>>20),I=v+(w^N&(E^w))+S[10]+4294925233&4294967295,v=N+(I<<17&4294967295|I>>>15),I=w+(E^v&(N^E))+S[11]+2304563134&4294967295,w=v+(I<<22&4294967295|I>>>10),I=E+(N^w&(v^N))+S[12]+1804603682&4294967295,E=w+(I<<7&4294967295|I>>>25),I=N+(v^E&(w^v))+S[13]+4254626195&4294967295,N=E+(I<<12&4294967295|I>>>20),I=v+(w^N&(E^w))+S[14]+2792965006&4294967295,v=N+(I<<17&4294967295|I>>>15),I=w+(E^v&(N^E))+S[15]+1236535329&4294967295,w=v+(I<<22&4294967295|I>>>10),I=E+(v^N&(w^v))+S[1]+4129170786&4294967295,E=w+(I<<5&4294967295|I>>>27),I=N+(w^v&(E^w))+S[6]+3225465664&4294967295,N=E+(I<<9&4294967295|I>>>23),I=v+(E^w&(N^E))+S[11]+643717713&4294967295,v=N+(I<<14&4294967295|I>>>18),I=w+(N^E&(v^N))+S[0]+3921069994&4294967295,w=v+(I<<20&4294967295|I>>>12),I=E+(v^N&(w^v))+S[5]+3593408605&4294967295,E=w+(I<<5&4294967295|I>>>27),I=N+(w^v&(E^w))+S[10]+38016083&4294967295,N=E+(I<<9&4294967295|I>>>23),I=v+(E^w&(N^E))+S[15]+3634488961&4294967295,v=N+(I<<14&4294967295|I>>>18),I=w+(N^E&(v^N))+S[4]+3889429448&4294967295,w=v+(I<<20&4294967295|I>>>12),I=E+(v^N&(w^v))+S[9]+568446438&4294967295,E=w+(I<<5&4294967295|I>>>27),I=N+(w^v&(E^w))+S[14]+3275163606&4294967295,N=E+(I<<9&4294967295|I>>>23),I=v+(E^w&(N^E))+S[3]+4107603335&4294967295,v=N+(I<<14&4294967295|I>>>18),I=w+(N^E&(v^N))+S[8]+1163531501&4294967295,w=v+(I<<20&4294967295|I>>>12),I=E+(v^N&(w^v))+S[13]+2850285829&4294967295,E=w+(I<<5&4294967295|I>>>27),I=N+(w^v&(E^w))+S[2]+4243563512&4294967295,N=E+(I<<9&4294967295|I>>>23),I=v+(E^w&(N^E))+S[7]+1735328473&4294967295,v=N+(I<<14&4294967295|I>>>18),I=w+(N^E&(v^N))+S[12]+2368359562&4294967295,w=v+(I<<20&4294967295|I>>>12),I=E+(w^v^N)+S[5]+4294588738&4294967295,E=w+(I<<4&4294967295|I>>>28),I=N+(E^w^v)+S[8]+2272392833&4294967295,N=E+(I<<11&4294967295|I>>>21),I=v+(N^E^w)+S[11]+1839030562&4294967295,v=N+(I<<16&4294967295|I>>>16),I=w+(v^N^E)+S[14]+4259657740&4294967295,w=v+(I<<23&4294967295|I>>>9),I=E+(w^v^N)+S[1]+2763975236&4294967295,E=w+(I<<4&4294967295|I>>>28),I=N+(E^w^v)+S[4]+1272893353&4294967295,N=E+(I<<11&4294967295|I>>>21),I=v+(N^E^w)+S[7]+4139469664&4294967295,v=N+(I<<16&4294967295|I>>>16),I=w+(v^N^E)+S[10]+3200236656&4294967295,w=v+(I<<23&4294967295|I>>>9),I=E+(w^v^N)+S[13]+681279174&4294967295,E=w+(I<<4&4294967295|I>>>28),I=N+(E^w^v)+S[0]+3936430074&4294967295,N=E+(I<<11&4294967295|I>>>21),I=v+(N^E^w)+S[3]+3572445317&4294967295,v=N+(I<<16&4294967295|I>>>16),I=w+(v^N^E)+S[6]+76029189&4294967295,w=v+(I<<23&4294967295|I>>>9),I=E+(w^v^N)+S[9]+3654602809&4294967295,E=w+(I<<4&4294967295|I>>>28),I=N+(E^w^v)+S[12]+3873151461&4294967295,N=E+(I<<11&4294967295|I>>>21),I=v+(N^E^w)+S[15]+530742520&4294967295,v=N+(I<<16&4294967295|I>>>16),I=w+(v^N^E)+S[2]+3299628645&4294967295,w=v+(I<<23&4294967295|I>>>9),I=E+(v^(w|~N))+S[0]+4096336452&4294967295,E=w+(I<<6&4294967295|I>>>26),I=N+(w^(E|~v))+S[7]+1126891415&4294967295,N=E+(I<<10&4294967295|I>>>22),I=v+(E^(N|~w))+S[14]+2878612391&4294967295,v=N+(I<<15&4294967295|I>>>17),I=w+(N^(v|~E))+S[5]+4237533241&4294967295,w=v+(I<<21&4294967295|I>>>11),I=E+(v^(w|~N))+S[12]+1700485571&4294967295,E=w+(I<<6&4294967295|I>>>26),I=N+(w^(E|~v))+S[3]+2399980690&4294967295,N=E+(I<<10&4294967295|I>>>22),I=v+(E^(N|~w))+S[10]+4293915773&4294967295,v=N+(I<<15&4294967295|I>>>17),I=w+(N^(v|~E))+S[1]+2240044497&4294967295,w=v+(I<<21&4294967295|I>>>11),I=E+(v^(w|~N))+S[8]+1873313359&4294967295,E=w+(I<<6&4294967295|I>>>26),I=N+(w^(E|~v))+S[15]+4264355552&4294967295,N=E+(I<<10&4294967295|I>>>22),I=v+(E^(N|~w))+S[6]+2734768916&4294967295,v=N+(I<<15&4294967295|I>>>17),I=w+(N^(v|~E))+S[13]+1309151649&4294967295,w=v+(I<<21&4294967295|I>>>11),I=E+(v^(w|~N))+S[4]+4149444226&4294967295,E=w+(I<<6&4294967295|I>>>26),I=N+(w^(E|~v))+S[11]+3174756917&4294967295,N=E+(I<<10&4294967295|I>>>22),I=v+(E^(N|~w))+S[2]+718787259&4294967295,v=N+(I<<15&4294967295|I>>>17),I=w+(N^(v|~E))+S[9]+3951481745&4294967295,A.g[0]=A.g[0]+E&4294967295,A.g[1]=A.g[1]+(v+(I<<21&4294967295|I>>>11))&4294967295,A.g[2]=A.g[2]+v&4294967295,A.g[3]=A.g[3]+N&4294967295}n.prototype.v=function(A,E){E===void 0&&(E=A.length);const w=E-this.blockSize,S=this.C;let v=this.h,N=0;for(;N<E;){if(v==0)for(;N<=w;)s(this,A,N),N+=this.blockSize;if(typeof A=="string"){for(;N<E;)if(S[v++]=A.charCodeAt(N++),v==this.blockSize){s(this,S),v=0;break}}else for(;N<E;)if(S[v++]=A[N++],v==this.blockSize){s(this,S),v=0;break}}this.h=v,this.o+=E},n.prototype.A=function(){var A=Array((this.h<56?this.blockSize:this.blockSize*2)-this.h);A[0]=128;for(var E=1;E<A.length-8;++E)A[E]=0;E=this.o*8;for(var w=A.length-8;w<A.length;++w)A[w]=E&255,E/=256;for(this.v(A),A=Array(16),E=0,w=0;w<4;++w)for(let S=0;S<32;S+=8)A[E++]=this.g[w]>>>S&255;return A};function i(A,E){var w=u;return Object.prototype.hasOwnProperty.call(w,A)?w[A]:w[A]=E(A)}function o(A,E){this.h=E;const w=[];let S=!0;for(let v=A.length-1;v>=0;v--){const N=A[v]|0;S&&N==E||(w[v]=N,S=!1)}this.g=w}var u={};function c(A){return-128<=A&&A<128?i(A,function(E){return new o([E|0],E<0?-1:0)}):new o([A|0],A<0?-1:0)}function l(A){if(isNaN(A)||!isFinite(A))return g;if(A<0)return q(l(-A));const E=[];let w=1;for(let S=0;A>=w;S++)E[S]=A/w|0,w*=4294967296;return new o(E,0)}function d(A,E){if(A.length==0)throw Error("number format error: empty string");if(E=E||10,E<2||36<E)throw Error("radix out of range: "+E);if(A.charAt(0)=="-")return q(d(A.substring(1),E));if(A.indexOf("-")>=0)throw Error('number format error: interior "-" character');const w=l(Math.pow(E,8));let S=g;for(let N=0;N<A.length;N+=8){var v=Math.min(8,A.length-N);const I=parseInt(A.substring(N,N+v),E);v<8?(v=l(Math.pow(E,v)),S=S.j(v).add(l(I))):(S=S.j(w),S=S.add(l(I)))}return S}var g=c(0),y=c(1),R=c(16777216);r=o.prototype,r.m=function(){if(M(this))return-q(this).m();let A=0,E=1;for(let w=0;w<this.g.length;w++){const S=this.i(w);A+=(S>=0?S:4294967296+S)*E,E*=4294967296}return A},r.toString=function(A){if(A=A||10,A<2||36<A)throw Error("radix out of range: "+A);if(C(this))return"0";if(M(this))return"-"+q(this).toString(A);const E=l(Math.pow(A,6));var w=this;let S="";for(;;){const v=Te(w,E).g;w=Q(w,v.j(E));let N=((w.g.length>0?w.g[0]:w.h)>>>0).toString(A);if(w=v,C(w))return N+S;for(;N.length<6;)N="0"+N;S=N+S}},r.i=function(A){return A<0?0:A<this.g.length?this.g[A]:this.h};function C(A){if(A.h!=0)return!1;for(let E=0;E<A.g.length;E++)if(A.g[E]!=0)return!1;return!0}function M(A){return A.h==-1}r.l=function(A){return A=Q(this,A),M(A)?-1:C(A)?0:1};function q(A){const E=A.g.length,w=[];for(let S=0;S<E;S++)w[S]=~A.g[S];return new o(w,~A.h).add(y)}r.abs=function(){return M(this)?q(this):this},r.add=function(A){const E=Math.max(this.g.length,A.g.length),w=[];let S=0;for(let v=0;v<=E;v++){let N=S+(this.i(v)&65535)+(A.i(v)&65535),I=(N>>>16)+(this.i(v)>>>16)+(A.i(v)>>>16);S=I>>>16,N&=65535,I&=65535,w[v]=I<<16|N}return new o(w,w[w.length-1]&-2147483648?-1:0)};function Q(A,E){return A.add(q(E))}r.j=function(A){if(C(this)||C(A))return g;if(M(this))return M(A)?q(this).j(q(A)):q(q(this).j(A));if(M(A))return q(this.j(q(A)));if(this.l(R)<0&&A.l(R)<0)return l(this.m()*A.m());const E=this.g.length+A.g.length,w=[];for(var S=0;S<2*E;S++)w[S]=0;for(S=0;S<this.g.length;S++)for(let v=0;v<A.g.length;v++){const N=this.i(S)>>>16,I=this.i(S)&65535,lt=A.i(v)>>>16,or=A.i(v)&65535;w[2*S+2*v]+=I*or,te(w,2*S+2*v),w[2*S+2*v+1]+=N*or,te(w,2*S+2*v+1),w[2*S+2*v+1]+=I*lt,te(w,2*S+2*v+1),w[2*S+2*v+2]+=N*lt,te(w,2*S+2*v+2)}for(A=0;A<E;A++)w[A]=w[2*A+1]<<16|w[2*A];for(A=E;A<2*E;A++)w[A]=0;return new o(w,0)};function te(A,E){for(;(A[E]&65535)!=A[E];)A[E+1]+=A[E]>>>16,A[E]&=65535,E++}function ne(A,E){this.g=A,this.h=E}function Te(A,E){if(C(E))throw Error("division by zero");if(C(A))return new ne(g,g);if(M(A))return E=Te(q(A),E),new ne(q(E.g),q(E.h));if(M(E))return E=Te(A,q(E)),new ne(q(E.g),E.h);if(A.g.length>30){if(M(A)||M(E))throw Error("slowDivide_ only works with positive integers.");for(var w=y,S=E;S.l(A)<=0;)w=de(w),S=de(S);var v=fe(w,1),N=fe(S,1);for(S=fe(S,2),w=fe(w,2);!C(S);){var I=N.add(S);I.l(A)<=0&&(v=v.add(w),N=I),S=fe(S,1),w=fe(w,1)}return E=Q(A,v.j(E)),new ne(v,E)}for(v=g;A.l(E)>=0;){for(w=Math.max(1,Math.floor(A.m()/E.m())),S=Math.ceil(Math.log(w)/Math.LN2),S=S<=48?1:Math.pow(2,S-48),N=l(w),I=N.j(E);M(I)||I.l(A)>0;)w-=S,N=l(w),I=N.j(E);C(N)&&(N=y),v=v.add(N),A=Q(A,I)}return new ne(v,A)}r.B=function(A){return Te(this,A).h},r.and=function(A){const E=Math.max(this.g.length,A.g.length),w=[];for(let S=0;S<E;S++)w[S]=this.i(S)&A.i(S);return new o(w,this.h&A.h)},r.or=function(A){const E=Math.max(this.g.length,A.g.length),w=[];for(let S=0;S<E;S++)w[S]=this.i(S)|A.i(S);return new o(w,this.h|A.h)},r.xor=function(A){const E=Math.max(this.g.length,A.g.length),w=[];for(let S=0;S<E;S++)w[S]=this.i(S)^A.i(S);return new o(w,this.h^A.h)};function de(A){const E=A.g.length+1,w=[];for(let S=0;S<E;S++)w[S]=A.i(S)<<1|A.i(S-1)>>>31;return new o(w,A.h)}function fe(A,E){const w=E>>5;E%=32;const S=A.g.length-w,v=[];for(let N=0;N<S;N++)v[N]=E>0?A.i(N+w)>>>E|A.i(N+w+1)<<32-E:A.i(N+w);return new o(v,A.h)}n.prototype.digest=n.prototype.A,n.prototype.reset=n.prototype.u,n.prototype.update=n.prototype.v,q2=n,o.prototype.add=o.prototype.add,o.prototype.multiply=o.prototype.j,o.prototype.modulo=o.prototype.B,o.prototype.compare=o.prototype.l,o.prototype.toNumber=o.prototype.m,o.prototype.toString=o.prototype.toString,o.prototype.getBits=o.prototype.i,o.fromNumber=l,o.fromString=d,qn=o}).apply(typeof id<"u"?id:typeof self<"u"?self:typeof window<"u"?window:{});var Wo=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};/** @license
Copyright The Closure Library Authors.
SPDX-License-Identifier: Apache-2.0
*/var $2,wi,G2,oa,Cc,j2,z2,H2;(function(){var r,e=Object.defineProperty;function t(a){a=[typeof globalThis=="object"&&globalThis,a,typeof window=="object"&&window,typeof self=="object"&&self,typeof Wo=="object"&&Wo];for(var h=0;h<a.length;++h){var f=a[h];if(f&&f.Math==Math)return f}throw Error("Cannot find global object")}var n=t(this);function s(a,h){if(h)e:{var f=n;a=a.split(".");for(var m=0;m<a.length-1;m++){var b=a[m];if(!(b in f))break e;f=f[b]}a=a[a.length-1],m=f[a],h=h(m),h!=m&&h!=null&&e(f,a,{configurable:!0,writable:!0,value:h})}}s("Symbol.dispose",function(a){return a||Symbol("Symbol.dispose")}),s("Array.prototype.values",function(a){return a||function(){return this[Symbol.iterator]()}}),s("Object.entries",function(a){return a||function(h){var f=[],m;for(m in h)Object.prototype.hasOwnProperty.call(h,m)&&f.push([m,h[m]]);return f}});/** @license

 Copyright The Closure Library Authors.
 SPDX-License-Identifier: Apache-2.0
*/var i=i||{},o=this||self;function u(a){var h=typeof a;return h=="object"&&a!=null||h=="function"}function c(a,h,f){return a.call.apply(a.bind,arguments)}function l(a,h,f){return l=c,l.apply(null,arguments)}function d(a,h){var f=Array.prototype.slice.call(arguments,1);return function(){var m=f.slice();return m.push.apply(m,arguments),a.apply(this,m)}}function g(a,h){function f(){}f.prototype=h.prototype,a.Z=h.prototype,a.prototype=new f,a.prototype.constructor=a,a.Ob=function(m,b,x){for(var G=Array(arguments.length-2),oe=2;oe<arguments.length;oe++)G[oe-2]=arguments[oe];return h.prototype[b].apply(m,G)}}var y=typeof AsyncContext<"u"&&typeof AsyncContext.Snapshot=="function"?a=>a&&AsyncContext.Snapshot.wrap(a):a=>a;function R(a){const h=a.length;if(h>0){const f=Array(h);for(let m=0;m<h;m++)f[m]=a[m];return f}return[]}function C(a,h){for(let m=1;m<arguments.length;m++){const b=arguments[m];var f=typeof b;if(f=f!="object"?f:b?Array.isArray(b)?"array":f:"null",f=="array"||f=="object"&&typeof b.length=="number"){f=a.length||0;const x=b.length||0;a.length=f+x;for(let G=0;G<x;G++)a[f+G]=b[G]}else a.push(b)}}class M{constructor(h,f){this.i=h,this.j=f,this.h=0,this.g=null}get(){let h;return this.h>0?(this.h--,h=this.g,this.g=h.next,h.next=null):h=this.i(),h}}function q(a){o.setTimeout(()=>{throw a},0)}function Q(){var a=A;let h=null;return a.g&&(h=a.g,a.g=a.g.next,a.g||(a.h=null),h.next=null),h}class te{constructor(){this.h=this.g=null}add(h,f){const m=ne.get();m.set(h,f),this.h?this.h.next=m:this.g=m,this.h=m}}var ne=new M(()=>new Te,a=>a.reset());class Te{constructor(){this.next=this.g=this.h=null}set(h,f){this.h=h,this.g=f,this.next=null}reset(){this.next=this.g=this.h=null}}let de,fe=!1,A=new te,E=()=>{const a=Promise.resolve(void 0);de=()=>{a.then(w)}};function w(){for(var a;a=Q();){try{a.h.call(a.g)}catch(f){q(f)}var h=ne;h.j(a),h.h<100&&(h.h++,a.next=h.g,h.g=a)}fe=!1}function S(){this.u=this.u,this.C=this.C}S.prototype.u=!1,S.prototype.dispose=function(){this.u||(this.u=!0,this.N())},S.prototype[Symbol.dispose]=function(){this.dispose()},S.prototype.N=function(){if(this.C)for(;this.C.length;)this.C.shift()()};function v(a,h){this.type=a,this.g=this.target=h,this.defaultPrevented=!1}v.prototype.h=function(){this.defaultPrevented=!0};var N=function(){if(!o.addEventListener||!Object.defineProperty)return!1;var a=!1,h=Object.defineProperty({},"passive",{get:function(){a=!0}});try{const f=()=>{};o.addEventListener("test",f,h),o.removeEventListener("test",f,h)}catch{}return a}();function I(a){return/^[\s\xa0]*$/.test(a)}function lt(a,h){v.call(this,a?a.type:""),this.relatedTarget=this.g=this.target=null,this.button=this.screenY=this.screenX=this.clientY=this.clientX=0,this.key="",this.metaKey=this.shiftKey=this.altKey=this.ctrlKey=!1,this.state=null,this.pointerId=0,this.pointerType="",this.i=null,a&&this.init(a,h)}g(lt,v),lt.prototype.init=function(a,h){const f=this.type=a.type,m=a.changedTouches&&a.changedTouches.length?a.changedTouches[0]:null;this.target=a.target||a.srcElement,this.g=h,h=a.relatedTarget,h||(f=="mouseover"?h=a.fromElement:f=="mouseout"&&(h=a.toElement)),this.relatedTarget=h,m?(this.clientX=m.clientX!==void 0?m.clientX:m.pageX,this.clientY=m.clientY!==void 0?m.clientY:m.pageY,this.screenX=m.screenX||0,this.screenY=m.screenY||0):(this.clientX=a.clientX!==void 0?a.clientX:a.pageX,this.clientY=a.clientY!==void 0?a.clientY:a.pageY,this.screenX=a.screenX||0,this.screenY=a.screenY||0),this.button=a.button,this.key=a.key||"",this.ctrlKey=a.ctrlKey,this.altKey=a.altKey,this.shiftKey=a.shiftKey,this.metaKey=a.metaKey,this.pointerId=a.pointerId||0,this.pointerType=a.pointerType,this.state=a.state,this.i=a,a.defaultPrevented&&lt.Z.h.call(this)},lt.prototype.h=function(){lt.Z.h.call(this);const a=this.i;a.preventDefault?a.preventDefault():a.returnValue=!1};var or="closure_listenable_"+(Math.random()*1e6|0),b7=0;function C7(a,h,f,m,b){this.listener=a,this.proxy=null,this.src=h,this.type=f,this.capture=!!m,this.ha=b,this.key=++b7,this.da=this.fa=!1}function Do(a){a.da=!0,a.listener=null,a.proxy=null,a.src=null,a.ha=null}function Oo(a,h,f){for(const m in a)h.call(f,a[m],m,a)}function N7(a,h){for(const f in a)h.call(void 0,a[f],f,a)}function Wl(a){const h={};for(const f in a)h[f]=a[f];return h}const Ql="constructor hasOwnProperty isPrototypeOf propertyIsEnumerable toLocaleString toString valueOf".split(" ");function Yl(a,h){let f,m;for(let b=1;b<arguments.length;b++){m=arguments[b];for(f in m)a[f]=m[f];for(let x=0;x<Ql.length;x++)f=Ql[x],Object.prototype.hasOwnProperty.call(m,f)&&(a[f]=m[f])}}function ko(a){this.src=a,this.g={},this.h=0}ko.prototype.add=function(a,h,f,m,b){const x=a.toString();a=this.g[x],a||(a=this.g[x]=[],this.h++);const G=Ou(a,h,m,b);return G>-1?(h=a[G],f||(h.fa=!1)):(h=new C7(h,this.src,x,!!m,b),h.fa=f,a.push(h)),h};function Du(a,h){const f=h.type;if(f in a.g){var m=a.g[f],b=Array.prototype.indexOf.call(m,h,void 0),x;(x=b>=0)&&Array.prototype.splice.call(m,b,1),x&&(Do(h),a.g[f].length==0&&(delete a.g[f],a.h--))}}function Ou(a,h,f,m){for(let b=0;b<a.length;++b){const x=a[b];if(!x.da&&x.listener==h&&x.capture==!!f&&x.ha==m)return b}return-1}var ku="closure_lm_"+(Math.random()*1e6|0),Lu={};function Xl(a,h,f,m,b){if(Array.isArray(h)){for(let x=0;x<h.length;x++)Xl(a,h[x],f,m,b);return null}return f=eh(f),a&&a[or]?a.J(h,f,u(m)?!!m.capture:!1,b):V7(a,h,f,!1,m,b)}function V7(a,h,f,m,b,x){if(!h)throw Error("Invalid event type");const G=u(b)?!!b.capture:!!b;let oe=Fu(a);if(oe||(a[ku]=oe=new ko(a)),f=oe.add(h,f,m,G,x),f.proxy)return f;if(m=x7(),f.proxy=m,m.src=a,m.listener=f,a.addEventListener)N||(b=G),b===void 0&&(b=!1),a.addEventListener(h.toString(),m,b);else if(a.attachEvent)a.attachEvent(Zl(h.toString()),m);else if(a.addListener&&a.removeListener)a.addListener(m);else throw Error("addEventListener and attachEvent are unavailable.");return f}function x7(){function a(f){return h.call(a.src,a.listener,f)}const h=D7;return a}function Jl(a,h,f,m,b){if(Array.isArray(h))for(var x=0;x<h.length;x++)Jl(a,h[x],f,m,b);else m=u(m)?!!m.capture:!!m,f=eh(f),a&&a[or]?(a=a.i,x=String(h).toString(),x in a.g&&(h=a.g[x],f=Ou(h,f,m,b),f>-1&&(Do(h[f]),Array.prototype.splice.call(h,f,1),h.length==0&&(delete a.g[x],a.h--)))):a&&(a=Fu(a))&&(h=a.g[h.toString()],a=-1,h&&(a=Ou(h,f,m,b)),(f=a>-1?h[a]:null)&&Mu(f))}function Mu(a){if(typeof a!="number"&&a&&!a.da){var h=a.src;if(h&&h[or])Du(h.i,a);else{var f=a.type,m=a.proxy;h.removeEventListener?h.removeEventListener(f,m,a.capture):h.detachEvent?h.detachEvent(Zl(f),m):h.addListener&&h.removeListener&&h.removeListener(m),(f=Fu(h))?(Du(f,a),f.h==0&&(f.src=null,h[ku]=null)):Do(a)}}}function Zl(a){return a in Lu?Lu[a]:Lu[a]="on"+a}function D7(a,h){if(a.da)a=!0;else{h=new lt(h,this);const f=a.listener,m=a.ha||a.src;a.fa&&Mu(a),a=f.call(m,h)}return a}function Fu(a){return a=a[ku],a instanceof ko?a:null}var Uu="__closure_events_fn_"+(Math.random()*1e9>>>0);function eh(a){return typeof a=="function"?a:(a[Uu]||(a[Uu]=function(h){return a.handleEvent(h)}),a[Uu])}function Ye(){S.call(this),this.i=new ko(this),this.M=this,this.G=null}g(Ye,S),Ye.prototype[or]=!0,Ye.prototype.removeEventListener=function(a,h,f,m){Jl(this,a,h,f,m)};function ot(a,h){var f,m=a.G;if(m)for(f=[];m;m=m.G)f.push(m);if(a=a.M,m=h.type||h,typeof h=="string")h=new v(h,a);else if(h instanceof v)h.target=h.target||a;else{var b=h;h=new v(m,a),Yl(h,b)}b=!0;let x,G;if(f)for(G=f.length-1;G>=0;G--)x=h.g=f[G],b=Lo(x,m,!0,h)&&b;if(x=h.g=a,b=Lo(x,m,!0,h)&&b,b=Lo(x,m,!1,h)&&b,f)for(G=0;G<f.length;G++)x=h.g=f[G],b=Lo(x,m,!1,h)&&b}Ye.prototype.N=function(){if(Ye.Z.N.call(this),this.i){var a=this.i;for(const h in a.g){const f=a.g[h];for(let m=0;m<f.length;m++)Do(f[m]);delete a.g[h],a.h--}}this.G=null},Ye.prototype.J=function(a,h,f,m){return this.i.add(String(a),h,!1,f,m)},Ye.prototype.K=function(a,h,f,m){return this.i.add(String(a),h,!0,f,m)};function Lo(a,h,f,m){if(h=a.i.g[String(h)],!h)return!0;h=h.concat();let b=!0;for(let x=0;x<h.length;++x){const G=h[x];if(G&&!G.da&&G.capture==f){const oe=G.listener,Ue=G.ha||G.src;G.fa&&Du(a.i,G),b=oe.call(Ue,m)!==!1&&b}}return b&&!m.defaultPrevented}function O7(a,h){if(typeof a!="function")if(a&&typeof a.handleEvent=="function")a=l(a.handleEvent,a);else throw Error("Invalid listener argument");return Number(h)>2147483647?-1:o.setTimeout(a,h||0)}function th(a){a.g=O7(()=>{a.g=null,a.i&&(a.i=!1,th(a))},a.l);const h=a.h;a.h=null,a.m.apply(null,h)}class k7 extends S{constructor(h,f){super(),this.m=h,this.l=f,this.h=null,this.i=!1,this.g=null}j(h){this.h=arguments,this.g?this.i=!0:th(this)}N(){super.N(),this.g&&(o.clearTimeout(this.g),this.g=null,this.i=!1,this.h=null)}}function Zs(a){S.call(this),this.h=a,this.g={}}g(Zs,S);var nh=[];function rh(a){Oo(a.g,function(h,f){this.g.hasOwnProperty(f)&&Mu(h)},a),a.g={}}Zs.prototype.N=function(){Zs.Z.N.call(this),rh(this)},Zs.prototype.handleEvent=function(){throw Error("EventHandler.handleEvent not implemented")};var Bu=o.JSON.stringify,L7=o.JSON.parse,M7=class{stringify(a){return o.JSON.stringify(a,void 0)}parse(a){return o.JSON.parse(a,void 0)}};function sh(){}function ih(){}var ei={OPEN:"a",hb:"b",ERROR:"c",tb:"d"};function qu(){v.call(this,"d")}g(qu,v);function $u(){v.call(this,"c")}g($u,v);var ar={},oh=null;function Mo(){return oh=oh||new Ye}ar.Ia="serverreachability";function ah(a){v.call(this,ar.Ia,a)}g(ah,v);function ti(a){const h=Mo();ot(h,new ah(h))}ar.STAT_EVENT="statevent";function uh(a,h){v.call(this,ar.STAT_EVENT,a),this.stat=h}g(uh,v);function at(a){const h=Mo();ot(h,new uh(h,a))}ar.Ja="timingevent";function ch(a,h){v.call(this,ar.Ja,a),this.size=h}g(ch,v);function ni(a,h){if(typeof a!="function")throw Error("Fn must not be null and must be a function");return o.setTimeout(function(){a()},h)}function ri(){this.g=!0}ri.prototype.ua=function(){this.g=!1};function F7(a,h,f,m,b,x){a.info(function(){if(a.g)if(x){var G="",oe=x.split("&");for(let Ee=0;Ee<oe.length;Ee++){var Ue=oe[Ee].split("=");if(Ue.length>1){const Ge=Ue[0];Ue=Ue[1];const Ut=Ge.split("_");G=Ut.length>=2&&Ut[1]=="type"?G+(Ge+"="+Ue+"&"):G+(Ge+"=redacted&")}}}else G=null;else G=x;return"XMLHTTP REQ ("+m+") [attempt "+b+"]: "+h+`
`+f+`
`+G})}function U7(a,h,f,m,b,x,G){a.info(function(){return"XMLHTTP RESP ("+m+") [ attempt "+b+"]: "+h+`
`+f+`
`+x+" "+G})}function zr(a,h,f,m){a.info(function(){return"XMLHTTP TEXT ("+h+"): "+q7(a,f)+(m?" "+m:"")})}function B7(a,h){a.info(function(){return"TIMEOUT: "+h})}ri.prototype.info=function(){};function q7(a,h){if(!a.g)return h;if(!h)return null;try{const x=JSON.parse(h);if(x){for(a=0;a<x.length;a++)if(Array.isArray(x[a])){var f=x[a];if(!(f.length<2)){var m=f[1];if(Array.isArray(m)&&!(m.length<1)){var b=m[0];if(b!="noop"&&b!="stop"&&b!="close")for(let G=1;G<m.length;G++)m[G]=""}}}}return Bu(x)}catch{return h}}var Fo={NO_ERROR:0,cb:1,qb:2,pb:3,kb:4,ob:5,rb:6,Ga:7,TIMEOUT:8,ub:9},lh={ib:"complete",Fb:"success",ERROR:"error",Ga:"abort",xb:"ready",yb:"readystatechange",TIMEOUT:"timeout",sb:"incrementaldata",wb:"progress",lb:"downloadprogress",Nb:"uploadprogress"},hh;function Gu(){}g(Gu,sh),Gu.prototype.g=function(){return new XMLHttpRequest},hh=new Gu;function si(a){return encodeURIComponent(String(a))}function $7(a){var h=1;a=a.split(":");const f=[];for(;h>0&&a.length;)f.push(a.shift()),h--;return a.length&&f.push(a.join(":")),f}function In(a,h,f,m){this.j=a,this.i=h,this.l=f,this.S=m||1,this.V=new Zs(this),this.H=45e3,this.J=null,this.o=!1,this.u=this.B=this.A=this.M=this.F=this.T=this.D=null,this.G=[],this.g=null,this.C=0,this.m=this.v=null,this.X=-1,this.K=!1,this.P=0,this.O=null,this.W=this.L=this.U=this.R=!1,this.h=new dh}function dh(){this.i=null,this.g="",this.h=!1}var fh={},ju={};function zu(a,h,f){a.M=1,a.A=Bo(Ft(h)),a.u=f,a.R=!0,ph(a,null)}function ph(a,h){a.F=Date.now(),Uo(a),a.B=Ft(a.A);var f=a.B,m=a.S;Array.isArray(m)||(m=[String(m)]),Ph(f.i,"t",m),a.C=0,f=a.j.L,a.h=new dh,a.g=zh(a.j,f?h:null,!a.u),a.P>0&&(a.O=new k7(l(a.Y,a,a.g),a.P)),h=a.V,f=a.g,m=a.ba;var b="readystatechange";Array.isArray(b)||(b&&(nh[0]=b.toString()),b=nh);for(let x=0;x<b.length;x++){const G=Xl(f,b[x],m||h.handleEvent,!1,h.h||h);if(!G)break;h.g[G.key]=G}h=a.J?Wl(a.J):{},a.u?(a.v||(a.v="POST"),h["Content-Type"]="application/x-www-form-urlencoded",a.g.ea(a.B,a.v,a.u,h)):(a.v="GET",a.g.ea(a.B,a.v,null,h)),ti(),F7(a.i,a.v,a.B,a.l,a.S,a.u)}In.prototype.ba=function(a){a=a.target;const h=this.O;h&&An(a)==3?h.j():this.Y(a)},In.prototype.Y=function(a){try{if(a==this.g)e:{const oe=An(this.g),Ue=this.g.ya(),Ee=this.g.ca();if(!(oe<3)&&(oe!=3||this.g&&(this.h.h||this.g.la()||Oh(this.g)))){this.K||oe!=4||Ue==7||(Ue==8||Ee<=0?ti(3):ti(2)),Hu(this);var h=this.g.ca();this.X=h;var f=G7(this);if(this.o=h==200,U7(this.i,this.v,this.B,this.l,this.S,oe,h),this.o){if(this.U&&!this.L){t:{if(this.g){var m,b=this.g;if((m=b.g?b.g.getResponseHeader("X-HTTP-Initial-Response"):null)&&!I(m)){var x=m;break t}}x=null}if(a=x)zr(this.i,this.l,a,"Initial handshake response via X-HTTP-Initial-Response"),this.L=!0,Ku(this,a);else{this.o=!1,this.m=3,at(12),ur(this),ii(this);break e}}if(this.R){a=!0;let Ge;for(;!this.K&&this.C<f.length;)if(Ge=j7(this,f),Ge==ju){oe==4&&(this.m=4,at(14),a=!1),zr(this.i,this.l,null,"[Incomplete Response]");break}else if(Ge==fh){this.m=4,at(15),zr(this.i,this.l,f,"[Invalid Chunk]"),a=!1;break}else zr(this.i,this.l,Ge,null),Ku(this,Ge);if(gh(this)&&this.C!=0&&(this.h.g=this.h.g.slice(this.C),this.C=0),oe!=4||f.length!=0||this.h.h||(this.m=1,at(16),a=!1),this.o=this.o&&a,!a)zr(this.i,this.l,f,"[Invalid Chunked Response]"),ur(this),ii(this);else if(f.length>0&&!this.W){this.W=!0;var G=this.j;G.g==this&&G.aa&&!G.P&&(G.j.info("Great, no buffering proxy detected. Bytes received: "+f.length),tc(G),G.P=!0,at(11))}}else zr(this.i,this.l,f,null),Ku(this,f);oe==4&&ur(this),this.o&&!this.K&&(oe==4?qh(this.j,this):(this.o=!1,Uo(this)))}else sg(this.g),h==400&&f.indexOf("Unknown SID")>0?(this.m=3,at(12)):(this.m=0,at(13)),ur(this),ii(this)}}}catch{}finally{}};function G7(a){if(!gh(a))return a.g.la();const h=Oh(a.g);if(h==="")return"";let f="";const m=h.length,b=An(a.g)==4;if(!a.h.i){if(typeof TextDecoder>"u")return ur(a),ii(a),"";a.h.i=new o.TextDecoder}for(let x=0;x<m;x++)a.h.h=!0,f+=a.h.i.decode(h[x],{stream:!(b&&x==m-1)});return h.length=0,a.h.g+=f,a.C=0,a.h.g}function gh(a){return a.g?a.v=="GET"&&a.M!=2&&a.j.Aa:!1}function j7(a,h){var f=a.C,m=h.indexOf(`
`,f);return m==-1?ju:(f=Number(h.substring(f,m)),isNaN(f)?fh:(m+=1,m+f>h.length?ju:(h=h.slice(m,m+f),a.C=m+f,h)))}In.prototype.cancel=function(){this.K=!0,ur(this)};function Uo(a){a.T=Date.now()+a.H,mh(a,a.H)}function mh(a,h){if(a.D!=null)throw Error("WatchDog timer not null");a.D=ni(l(a.aa,a),h)}function Hu(a){a.D&&(o.clearTimeout(a.D),a.D=null)}In.prototype.aa=function(){this.D=null;const a=Date.now();a-this.T>=0?(B7(this.i,this.B),this.M!=2&&(ti(),at(17)),ur(this),this.m=2,ii(this)):mh(this,this.T-a)};function ii(a){a.j.I==0||a.K||qh(a.j,a)}function ur(a){Hu(a);var h=a.O;h&&typeof h.dispose=="function"&&h.dispose(),a.O=null,rh(a.V),a.g&&(h=a.g,a.g=null,h.abort(),h.dispose())}function Ku(a,h){try{var f=a.j;if(f.I!=0&&(f.g==a||Wu(f.h,a))){if(!a.L&&Wu(f.h,a)&&f.I==3){try{var m=f.Ba.g.parse(h)}catch{m=null}if(Array.isArray(m)&&m.length==3){var b=m;if(b[0]==0){e:if(!f.v){if(f.g)if(f.g.F+3e3<a.F)zo(f),Go(f);else break e;ec(f),at(18)}}else f.xa=b[1],0<f.xa-f.K&&b[2]<37500&&f.F&&f.A==0&&!f.C&&(f.C=ni(l(f.Va,f),6e3));Eh(f.h)<=1&&f.ta&&(f.ta=void 0)}else lr(f,11)}else if((a.L||f.g==a)&&zo(f),!I(h))for(b=f.Ba.g.parse(h),h=0;h<b.length;h++){let Ee=b[h];const Ge=Ee[0];if(!(Ge<=f.K))if(f.K=Ge,Ee=Ee[1],f.I==2)if(Ee[0]=="c"){f.M=Ee[1],f.ba=Ee[2];const Ut=Ee[3];Ut!=null&&(f.ka=Ut,f.j.info("VER="+f.ka));const hr=Ee[4];hr!=null&&(f.za=hr,f.j.info("SVER="+f.za));const vn=Ee[5];vn!=null&&typeof vn=="number"&&vn>0&&(m=1.5*vn,f.O=m,f.j.info("backChannelRequestTimeoutMs_="+m)),m=f;const Rn=a.g;if(Rn){const Ko=Rn.g?Rn.g.getResponseHeader("X-Client-Wire-Protocol"):null;if(Ko){var x=m.h;x.g||Ko.indexOf("spdy")==-1&&Ko.indexOf("quic")==-1&&Ko.indexOf("h2")==-1||(x.j=x.l,x.g=new Set,x.h&&(Qu(x,x.h),x.h=null))}if(m.G){const nc=Rn.g?Rn.g.getResponseHeader("X-HTTP-Session-Id"):null;nc&&(m.wa=nc,Ae(m.J,m.G,nc))}}f.I=3,f.l&&f.l.ra(),f.aa&&(f.T=Date.now()-a.F,f.j.info("Handshake RTT: "+f.T+"ms")),m=f;var G=a;if(m.na=jh(m,m.L?m.ba:null,m.W),G.L){Ih(m.h,G);var oe=G,Ue=m.O;Ue&&(oe.H=Ue),oe.D&&(Hu(oe),Uo(oe)),m.g=G}else Uh(m);f.i.length>0&&jo(f)}else Ee[0]!="stop"&&Ee[0]!="close"||lr(f,7);else f.I==3&&(Ee[0]=="stop"||Ee[0]=="close"?Ee[0]=="stop"?lr(f,7):Zu(f):Ee[0]!="noop"&&f.l&&f.l.qa(Ee),f.A=0)}}ti(4)}catch{}}var z7=class{constructor(a,h){this.g=a,this.map=h}};function _h(a){this.l=a||10,o.PerformanceNavigationTiming?(a=o.performance.getEntriesByType("navigation"),a=a.length>0&&(a[0].nextHopProtocol=="hq"||a[0].nextHopProtocol=="h2")):a=!!(o.chrome&&o.chrome.loadTimes&&o.chrome.loadTimes()&&o.chrome.loadTimes().wasFetchedViaSpdy),this.j=a?this.l:1,this.g=null,this.j>1&&(this.g=new Set),this.h=null,this.i=[]}function yh(a){return a.h?!0:a.g?a.g.size>=a.j:!1}function Eh(a){return a.h?1:a.g?a.g.size:0}function Wu(a,h){return a.h?a.h==h:a.g?a.g.has(h):!1}function Qu(a,h){a.g?a.g.add(h):a.h=h}function Ih(a,h){a.h&&a.h==h?a.h=null:a.g&&a.g.has(h)&&a.g.delete(h)}_h.prototype.cancel=function(){if(this.i=wh(this),this.h)this.h.cancel(),this.h=null;else if(this.g&&this.g.size!==0){for(const a of this.g.values())a.cancel();this.g.clear()}};function wh(a){if(a.h!=null)return a.i.concat(a.h.G);if(a.g!=null&&a.g.size!==0){let h=a.i;for(const f of a.g.values())h=h.concat(f.G);return h}return R(a.i)}var Th=RegExp("^(?:([^:/?#.]+):)?(?://(?:([^\\\\/?#]*)@)?([^\\\\/?#]*?)(?::([0-9]+))?(?=[\\\\/?#]|$))?([^?#]+)?(?:\\?([^#]*))?(?:#([\\s\\S]*))?$");function H7(a,h){if(a){a=a.split("&");for(let f=0;f<a.length;f++){const m=a[f].indexOf("=");let b,x=null;m>=0?(b=a[f].substring(0,m),x=a[f].substring(m+1)):b=a[f],h(b,x?decodeURIComponent(x.replace(/\+/g," ")):"")}}}function wn(a){this.g=this.o=this.j="",this.u=null,this.m=this.h="",this.l=!1;let h;a instanceof wn?(this.l=a.l,oi(this,a.j),this.o=a.o,this.g=a.g,ai(this,a.u),this.h=a.h,Yu(this,bh(a.i)),this.m=a.m):a&&(h=String(a).match(Th))?(this.l=!1,oi(this,h[1]||"",!0),this.o=ui(h[2]||""),this.g=ui(h[3]||"",!0),ai(this,h[4]),this.h=ui(h[5]||"",!0),Yu(this,h[6]||"",!0),this.m=ui(h[7]||"")):(this.l=!1,this.i=new li(null,this.l))}wn.prototype.toString=function(){const a=[];var h=this.j;h&&a.push(ci(h,Ah,!0),":");var f=this.g;return(f||h=="file")&&(a.push("//"),(h=this.o)&&a.push(ci(h,Ah,!0),"@"),a.push(si(f).replace(/%25([0-9a-fA-F]{2})/g,"%$1")),f=this.u,f!=null&&a.push(":",String(f))),(f=this.h)&&(this.g&&f.charAt(0)!="/"&&a.push("/"),a.push(ci(f,f.charAt(0)=="/"?Q7:W7,!0))),(f=this.i.toString())&&a.push("?",f),(f=this.m)&&a.push("#",ci(f,X7)),a.join("")},wn.prototype.resolve=function(a){const h=Ft(this);let f=!!a.j;f?oi(h,a.j):f=!!a.o,f?h.o=a.o:f=!!a.g,f?h.g=a.g:f=a.u!=null;var m=a.h;if(f)ai(h,a.u);else if(f=!!a.h){if(m.charAt(0)!="/")if(this.g&&!this.h)m="/"+m;else{var b=h.h.lastIndexOf("/");b!=-1&&(m=h.h.slice(0,b+1)+m)}if(b=m,b==".."||b==".")m="";else if(b.indexOf("./")!=-1||b.indexOf("/.")!=-1){m=b.lastIndexOf("/",0)==0,b=b.split("/");const x=[];for(let G=0;G<b.length;){const oe=b[G++];oe=="."?m&&G==b.length&&x.push(""):oe==".."?((x.length>1||x.length==1&&x[0]!="")&&x.pop(),m&&G==b.length&&x.push("")):(x.push(oe),m=!0)}m=x.join("/")}else m=b}return f?h.h=m:f=a.i.toString()!=="",f?Yu(h,bh(a.i)):f=!!a.m,f&&(h.m=a.m),h};function Ft(a){return new wn(a)}function oi(a,h,f){a.j=f?ui(h,!0):h,a.j&&(a.j=a.j.replace(/:$/,""))}function ai(a,h){if(h){if(h=Number(h),isNaN(h)||h<0)throw Error("Bad port number "+h);a.u=h}else a.u=null}function Yu(a,h,f){h instanceof li?(a.i=h,J7(a.i,a.l)):(f||(h=ci(h,Y7)),a.i=new li(h,a.l))}function Ae(a,h,f){a.i.set(h,f)}function Bo(a){return Ae(a,"zx",Math.floor(Math.random()*2147483648).toString(36)+Math.abs(Math.floor(Math.random()*2147483648)^Date.now()).toString(36)),a}function ui(a,h){return a?h?decodeURI(a.replace(/%25/g,"%2525")):decodeURIComponent(a):""}function ci(a,h,f){return typeof a=="string"?(a=encodeURI(a).replace(h,K7),f&&(a=a.replace(/%25([0-9a-fA-F]{2})/g,"%$1")),a):null}function K7(a){return a=a.charCodeAt(0),"%"+(a>>4&15).toString(16)+(a&15).toString(16)}var Ah=/[#\/\?@]/g,W7=/[#\?:]/g,Q7=/[#\?]/g,Y7=/[#\?@]/g,X7=/#/g;function li(a,h){this.h=this.g=null,this.i=a||null,this.j=!!h}function cr(a){a.g||(a.g=new Map,a.h=0,a.i&&H7(a.i,function(h,f){a.add(decodeURIComponent(h.replace(/\+/g," ")),f)}))}r=li.prototype,r.add=function(a,h){cr(this),this.i=null,a=Hr(this,a);let f=this.g.get(a);return f||this.g.set(a,f=[]),f.push(h),this.h+=1,this};function vh(a,h){cr(a),h=Hr(a,h),a.g.has(h)&&(a.i=null,a.h-=a.g.get(h).length,a.g.delete(h))}function Rh(a,h){return cr(a),h=Hr(a,h),a.g.has(h)}r.forEach=function(a,h){cr(this),this.g.forEach(function(f,m){f.forEach(function(b){a.call(h,b,m,this)},this)},this)};function Sh(a,h){cr(a);let f=[];if(typeof h=="string")Rh(a,h)&&(f=f.concat(a.g.get(Hr(a,h))));else for(a=Array.from(a.g.values()),h=0;h<a.length;h++)f=f.concat(a[h]);return f}r.set=function(a,h){return cr(this),this.i=null,a=Hr(this,a),Rh(this,a)&&(this.h-=this.g.get(a).length),this.g.set(a,[h]),this.h+=1,this},r.get=function(a,h){return a?(a=Sh(this,a),a.length>0?String(a[0]):h):h};function Ph(a,h,f){vh(a,h),f.length>0&&(a.i=null,a.g.set(Hr(a,h),R(f)),a.h+=f.length)}r.toString=function(){if(this.i)return this.i;if(!this.g)return"";const a=[],h=Array.from(this.g.keys());for(let m=0;m<h.length;m++){var f=h[m];const b=si(f);f=Sh(this,f);for(let x=0;x<f.length;x++){let G=b;f[x]!==""&&(G+="="+si(f[x])),a.push(G)}}return this.i=a.join("&")};function bh(a){const h=new li;return h.i=a.i,a.g&&(h.g=new Map(a.g),h.h=a.h),h}function Hr(a,h){return h=String(h),a.j&&(h=h.toLowerCase()),h}function J7(a,h){h&&!a.j&&(cr(a),a.i=null,a.g.forEach(function(f,m){const b=m.toLowerCase();m!=b&&(vh(this,m),Ph(this,b,f))},a)),a.j=h}function Z7(a,h){const f=new ri;if(o.Image){const m=new Image;m.onload=d(Tn,f,"TestLoadImage: loaded",!0,h,m),m.onerror=d(Tn,f,"TestLoadImage: error",!1,h,m),m.onabort=d(Tn,f,"TestLoadImage: abort",!1,h,m),m.ontimeout=d(Tn,f,"TestLoadImage: timeout",!1,h,m),o.setTimeout(function(){m.ontimeout&&m.ontimeout()},1e4),m.src=a}else h(!1)}function eg(a,h){const f=new ri,m=new AbortController,b=setTimeout(()=>{m.abort(),Tn(f,"TestPingServer: timeout",!1,h)},1e4);fetch(a,{signal:m.signal}).then(x=>{clearTimeout(b),x.ok?Tn(f,"TestPingServer: ok",!0,h):Tn(f,"TestPingServer: server error",!1,h)}).catch(()=>{clearTimeout(b),Tn(f,"TestPingServer: error",!1,h)})}function Tn(a,h,f,m,b){try{b&&(b.onload=null,b.onerror=null,b.onabort=null,b.ontimeout=null),m(f)}catch{}}function tg(){this.g=new M7}function Xu(a){this.i=a.Sb||null,this.h=a.ab||!1}g(Xu,sh),Xu.prototype.g=function(){return new qo(this.i,this.h)};function qo(a,h){Ye.call(this),this.H=a,this.o=h,this.m=void 0,this.status=this.readyState=0,this.responseType=this.responseText=this.response=this.statusText="",this.onreadystatechange=null,this.A=new Headers,this.h=null,this.F="GET",this.D="",this.g=!1,this.B=this.j=this.l=null,this.v=new AbortController}g(qo,Ye),r=qo.prototype,r.open=function(a,h){if(this.readyState!=0)throw this.abort(),Error("Error reopening a connection");this.F=a,this.D=h,this.readyState=1,di(this)},r.send=function(a){if(this.readyState!=1)throw this.abort(),Error("need to call open() first. ");if(this.v.signal.aborted)throw this.abort(),Error("Request was aborted.");this.g=!0;const h={headers:this.A,method:this.F,credentials:this.m,cache:void 0,signal:this.v.signal};a&&(h.body=a),(this.H||o).fetch(new Request(this.D,h)).then(this.Pa.bind(this),this.ga.bind(this))},r.abort=function(){this.response=this.responseText="",this.A=new Headers,this.status=0,this.v.abort(),this.j&&this.j.cancel("Request was aborted.").catch(()=>{}),this.readyState>=1&&this.g&&this.readyState!=4&&(this.g=!1,hi(this)),this.readyState=0},r.Pa=function(a){if(this.g&&(this.l=a,this.h||(this.status=this.l.status,this.statusText=this.l.statusText,this.h=a.headers,this.readyState=2,di(this)),this.g&&(this.readyState=3,di(this),this.g)))if(this.responseType==="arraybuffer")a.arrayBuffer().then(this.Na.bind(this),this.ga.bind(this));else if(typeof o.ReadableStream<"u"&&"body"in a){if(this.j=a.body.getReader(),this.o){if(this.responseType)throw Error('responseType must be empty for "streamBinaryChunks" mode responses.');this.response=[]}else this.response=this.responseText="",this.B=new TextDecoder;Ch(this)}else a.text().then(this.Oa.bind(this),this.ga.bind(this))};function Ch(a){a.j.read().then(a.Ma.bind(a)).catch(a.ga.bind(a))}r.Ma=function(a){if(this.g){if(this.o&&a.value)this.response.push(a.value);else if(!this.o){var h=a.value?a.value:new Uint8Array(0);(h=this.B.decode(h,{stream:!a.done}))&&(this.response=this.responseText+=h)}a.done?hi(this):di(this),this.readyState==3&&Ch(this)}},r.Oa=function(a){this.g&&(this.response=this.responseText=a,hi(this))},r.Na=function(a){this.g&&(this.response=a,hi(this))},r.ga=function(){this.g&&hi(this)};function hi(a){a.readyState=4,a.l=null,a.j=null,a.B=null,di(a)}r.setRequestHeader=function(a,h){this.A.append(a,h)},r.getResponseHeader=function(a){return this.h&&this.h.get(a.toLowerCase())||""},r.getAllResponseHeaders=function(){if(!this.h)return"";const a=[],h=this.h.entries();for(var f=h.next();!f.done;)f=f.value,a.push(f[0]+": "+f[1]),f=h.next();return a.join(`\r
`)};function di(a){a.onreadystatechange&&a.onreadystatechange.call(a)}Object.defineProperty(qo.prototype,"withCredentials",{get:function(){return this.m==="include"},set:function(a){this.m=a?"include":"same-origin"}});function Nh(a){let h="";return Oo(a,function(f,m){h+=m,h+=":",h+=f,h+=`\r
`}),h}function Ju(a,h,f){e:{for(m in f){var m=!1;break e}m=!0}m||(f=Nh(f),typeof a=="string"?f!=null&&si(f):Ae(a,h,f))}function Ve(a){Ye.call(this),this.headers=new Map,this.L=a||null,this.h=!1,this.g=null,this.D="",this.o=0,this.l="",this.j=this.B=this.v=this.A=!1,this.m=null,this.F="",this.H=!1}g(Ve,Ye);var ng=/^https?$/i,rg=["POST","PUT"];r=Ve.prototype,r.Fa=function(a){this.H=a},r.ea=function(a,h,f,m){if(this.g)throw Error("[goog.net.XhrIo] Object is active with another request="+this.D+"; newUri="+a);h=h?h.toUpperCase():"GET",this.D=a,this.l="",this.o=0,this.A=!1,this.h=!0,this.g=this.L?this.L.g():hh.g(),this.g.onreadystatechange=y(l(this.Ca,this));try{this.B=!0,this.g.open(h,String(a),!0),this.B=!1}catch(x){Vh(this,x);return}if(a=f||"",f=new Map(this.headers),m)if(Object.getPrototypeOf(m)===Object.prototype)for(var b in m)f.set(b,m[b]);else if(typeof m.keys=="function"&&typeof m.get=="function")for(const x of m.keys())f.set(x,m.get(x));else throw Error("Unknown input type for opt_headers: "+String(m));m=Array.from(f.keys()).find(x=>x.toLowerCase()=="content-type"),b=o.FormData&&a instanceof o.FormData,!(Array.prototype.indexOf.call(rg,h,void 0)>=0)||m||b||f.set("Content-Type","application/x-www-form-urlencoded;charset=utf-8");for(const[x,G]of f)this.g.setRequestHeader(x,G);this.F&&(this.g.responseType=this.F),"withCredentials"in this.g&&this.g.withCredentials!==this.H&&(this.g.withCredentials=this.H);try{this.m&&(clearTimeout(this.m),this.m=null),this.v=!0,this.g.send(a),this.v=!1}catch(x){Vh(this,x)}};function Vh(a,h){a.h=!1,a.g&&(a.j=!0,a.g.abort(),a.j=!1),a.l=h,a.o=5,xh(a),$o(a)}function xh(a){a.A||(a.A=!0,ot(a,"complete"),ot(a,"error"))}r.abort=function(a){this.g&&this.h&&(this.h=!1,this.j=!0,this.g.abort(),this.j=!1,this.o=a||7,ot(this,"complete"),ot(this,"abort"),$o(this))},r.N=function(){this.g&&(this.h&&(this.h=!1,this.j=!0,this.g.abort(),this.j=!1),$o(this,!0)),Ve.Z.N.call(this)},r.Ca=function(){this.u||(this.B||this.v||this.j?Dh(this):this.Xa())},r.Xa=function(){Dh(this)};function Dh(a){if(a.h&&typeof i<"u"){if(a.v&&An(a)==4)setTimeout(a.Ca.bind(a),0);else if(ot(a,"readystatechange"),An(a)==4){a.h=!1;try{const x=a.ca();e:switch(x){case 200:case 201:case 202:case 204:case 206:case 304:case 1223:var h=!0;break e;default:h=!1}var f;if(!(f=h)){var m;if(m=x===0){let G=String(a.D).match(Th)[1]||null;!G&&o.self&&o.self.location&&(G=o.self.location.protocol.slice(0,-1)),m=!ng.test(G?G.toLowerCase():"")}f=m}if(f)ot(a,"complete"),ot(a,"success");else{a.o=6;try{var b=An(a)>2?a.g.statusText:""}catch{b=""}a.l=b+" ["+a.ca()+"]",xh(a)}}finally{$o(a)}}}}function $o(a,h){if(a.g){a.m&&(clearTimeout(a.m),a.m=null);const f=a.g;a.g=null,h||ot(a,"ready");try{f.onreadystatechange=null}catch{}}}r.isActive=function(){return!!this.g};function An(a){return a.g?a.g.readyState:0}r.ca=function(){try{return An(this)>2?this.g.status:-1}catch{return-1}},r.la=function(){try{return this.g?this.g.responseText:""}catch{return""}},r.La=function(a){if(this.g){var h=this.g.responseText;return a&&h.indexOf(a)==0&&(h=h.substring(a.length)),L7(h)}};function Oh(a){try{if(!a.g)return null;if("response"in a.g)return a.g.response;switch(a.F){case"":case"text":return a.g.responseText;case"arraybuffer":if("mozResponseArrayBuffer"in a.g)return a.g.mozResponseArrayBuffer}return null}catch{return null}}function sg(a){const h={};a=(a.g&&An(a)>=2&&a.g.getAllResponseHeaders()||"").split(`\r
`);for(let m=0;m<a.length;m++){if(I(a[m]))continue;var f=$7(a[m]);const b=f[0];if(f=f[1],typeof f!="string")continue;f=f.trim();const x=h[b]||[];h[b]=x,x.push(f)}N7(h,function(m){return m.join(", ")})}r.ya=function(){return this.o},r.Ha=function(){return typeof this.l=="string"?this.l:String(this.l)};function fi(a,h,f){return f&&f.internalChannelParams&&f.internalChannelParams[a]||h}function kh(a){this.za=0,this.i=[],this.j=new ri,this.ba=this.na=this.J=this.W=this.g=this.wa=this.G=this.H=this.u=this.U=this.o=null,this.Ya=this.V=0,this.Sa=fi("failFast",!1,a),this.F=this.C=this.v=this.m=this.l=null,this.X=!0,this.xa=this.K=-1,this.Y=this.A=this.D=0,this.Qa=fi("baseRetryDelayMs",5e3,a),this.Za=fi("retryDelaySeedMs",1e4,a),this.Ta=fi("forwardChannelMaxRetries",2,a),this.va=fi("forwardChannelRequestTimeoutMs",2e4,a),this.ma=a&&a.xmlHttpFactory||void 0,this.Ua=a&&a.Rb||void 0,this.Aa=a&&a.useFetchStreams||!1,this.O=void 0,this.L=a&&a.supportsCrossDomainXhr||!1,this.M="",this.h=new _h(a&&a.concurrentRequestLimit),this.Ba=new tg,this.S=a&&a.fastHandshake||!1,this.R=a&&a.encodeInitMessageHeaders||!1,this.S&&this.R&&(this.R=!1),this.Ra=a&&a.Pb||!1,a&&a.ua&&this.j.ua(),a&&a.forceLongPolling&&(this.X=!1),this.aa=!this.S&&this.X&&a&&a.detectBufferingProxy||!1,this.ia=void 0,a&&a.longPollingTimeout&&a.longPollingTimeout>0&&(this.ia=a.longPollingTimeout),this.ta=void 0,this.T=0,this.P=!1,this.ja=this.B=null}r=kh.prototype,r.ka=8,r.I=1,r.connect=function(a,h,f,m){at(0),this.W=a,this.H=h||{},f&&m!==void 0&&(this.H.OSID=f,this.H.OAID=m),this.F=this.X,this.J=jh(this,null,this.W),jo(this)};function Zu(a){if(Lh(a),a.I==3){var h=a.V++,f=Ft(a.J);if(Ae(f,"SID",a.M),Ae(f,"RID",h),Ae(f,"TYPE","terminate"),pi(a,f),h=new In(a,a.j,h),h.M=2,h.A=Bo(Ft(f)),f=!1,o.navigator&&o.navigator.sendBeacon)try{f=o.navigator.sendBeacon(h.A.toString(),"")}catch{}!f&&o.Image&&(new Image().src=h.A,f=!0),f||(h.g=zh(h.j,null),h.g.ea(h.A)),h.F=Date.now(),Uo(h)}Gh(a)}function Go(a){a.g&&(tc(a),a.g.cancel(),a.g=null)}function Lh(a){Go(a),a.v&&(o.clearTimeout(a.v),a.v=null),zo(a),a.h.cancel(),a.m&&(typeof a.m=="number"&&o.clearTimeout(a.m),a.m=null)}function jo(a){if(!yh(a.h)&&!a.m){a.m=!0;var h=a.Ea;de||E(),fe||(de(),fe=!0),A.add(h,a),a.D=0}}function ig(a,h){return Eh(a.h)>=a.h.j-(a.m?1:0)?!1:a.m?(a.i=h.G.concat(a.i),!0):a.I==1||a.I==2||a.D>=(a.Sa?0:a.Ta)?!1:(a.m=ni(l(a.Ea,a,h),$h(a,a.D)),a.D++,!0)}r.Ea=function(a){if(this.m)if(this.m=null,this.I==1){if(!a){this.V=Math.floor(Math.random()*1e5),a=this.V++;const b=new In(this,this.j,a);let x=this.o;if(this.U&&(x?(x=Wl(x),Yl(x,this.U)):x=this.U),this.u!==null||this.R||(b.J=x,x=null),this.S)e:{for(var h=0,f=0;f<this.i.length;f++){t:{var m=this.i[f];if("__data__"in m.map&&(m=m.map.__data__,typeof m=="string")){m=m.length;break t}m=void 0}if(m===void 0)break;if(h+=m,h>4096){h=f;break e}if(h===4096||f===this.i.length-1){h=f+1;break e}}h=1e3}else h=1e3;h=Fh(this,b,h),f=Ft(this.J),Ae(f,"RID",a),Ae(f,"CVER",22),this.G&&Ae(f,"X-HTTP-Session-Id",this.G),pi(this,f),x&&(this.R?h="headers="+si(Nh(x))+"&"+h:this.u&&Ju(f,this.u,x)),Qu(this.h,b),this.Ra&&Ae(f,"TYPE","init"),this.S?(Ae(f,"$req",h),Ae(f,"SID","null"),b.U=!0,zu(b,f,null)):zu(b,f,h),this.I=2}}else this.I==3&&(a?Mh(this,a):this.i.length==0||yh(this.h)||Mh(this))};function Mh(a,h){var f;h?f=h.l:f=a.V++;const m=Ft(a.J);Ae(m,"SID",a.M),Ae(m,"RID",f),Ae(m,"AID",a.K),pi(a,m),a.u&&a.o&&Ju(m,a.u,a.o),f=new In(a,a.j,f,a.D+1),a.u===null&&(f.J=a.o),h&&(a.i=h.G.concat(a.i)),h=Fh(a,f,1e3),f.H=Math.round(a.va*.5)+Math.round(a.va*.5*Math.random()),Qu(a.h,f),zu(f,m,h)}function pi(a,h){a.H&&Oo(a.H,function(f,m){Ae(h,m,f)}),a.l&&Oo({},function(f,m){Ae(h,m,f)})}function Fh(a,h,f){f=Math.min(a.i.length,f);const m=a.l?l(a.l.Ka,a.l,a):null;e:{var b=a.i;let oe=-1;for(;;){const Ue=["count="+f];oe==-1?f>0?(oe=b[0].g,Ue.push("ofs="+oe)):oe=0:Ue.push("ofs="+oe);let Ee=!0;for(let Ge=0;Ge<f;Ge++){var x=b[Ge].g;const Ut=b[Ge].map;if(x-=oe,x<0)oe=Math.max(0,b[Ge].g-100),Ee=!1;else try{x="req"+x+"_"||"";try{var G=Ut instanceof Map?Ut:Object.entries(Ut);for(const[hr,vn]of G){let Rn=vn;u(vn)&&(Rn=Bu(vn)),Ue.push(x+hr+"="+encodeURIComponent(Rn))}}catch(hr){throw Ue.push(x+"type="+encodeURIComponent("_badmap")),hr}}catch{m&&m(Ut)}}if(Ee){G=Ue.join("&");break e}}G=void 0}return a=a.i.splice(0,f),h.G=a,G}function Uh(a){if(!a.g&&!a.v){a.Y=1;var h=a.Da;de||E(),fe||(de(),fe=!0),A.add(h,a),a.A=0}}function ec(a){return a.g||a.v||a.A>=3?!1:(a.Y++,a.v=ni(l(a.Da,a),$h(a,a.A)),a.A++,!0)}r.Da=function(){if(this.v=null,Bh(this),this.aa&&!(this.P||this.g==null||this.T<=0)){var a=4*this.T;this.j.info("BP detection timer enabled: "+a),this.B=ni(l(this.Wa,this),a)}},r.Wa=function(){this.B&&(this.B=null,this.j.info("BP detection timeout reached."),this.j.info("Buffering proxy detected and switch to long-polling!"),this.F=!1,this.P=!0,at(10),Go(this),Bh(this))};function tc(a){a.B!=null&&(o.clearTimeout(a.B),a.B=null)}function Bh(a){a.g=new In(a,a.j,"rpc",a.Y),a.u===null&&(a.g.J=a.o),a.g.P=0;var h=Ft(a.na);Ae(h,"RID","rpc"),Ae(h,"SID",a.M),Ae(h,"AID",a.K),Ae(h,"CI",a.F?"0":"1"),!a.F&&a.ia&&Ae(h,"TO",a.ia),Ae(h,"TYPE","xmlhttp"),pi(a,h),a.u&&a.o&&Ju(h,a.u,a.o),a.O&&(a.g.H=a.O);var f=a.g;a=a.ba,f.M=1,f.A=Bo(Ft(h)),f.u=null,f.R=!0,ph(f,a)}r.Va=function(){this.C!=null&&(this.C=null,Go(this),ec(this),at(19))};function zo(a){a.C!=null&&(o.clearTimeout(a.C),a.C=null)}function qh(a,h){var f=null;if(a.g==h){zo(a),tc(a),a.g=null;var m=2}else if(Wu(a.h,h))f=h.G,Ih(a.h,h),m=1;else return;if(a.I!=0){if(h.o)if(m==1){f=h.u?h.u.length:0,h=Date.now()-h.F;var b=a.D;m=Mo(),ot(m,new ch(m,f)),jo(a)}else Uh(a);else if(b=h.m,b==3||b==0&&h.X>0||!(m==1&&ig(a,h)||m==2&&ec(a)))switch(f&&f.length>0&&(h=a.h,h.i=h.i.concat(f)),b){case 1:lr(a,5);break;case 4:lr(a,10);break;case 3:lr(a,6);break;default:lr(a,2)}}}function $h(a,h){let f=a.Qa+Math.floor(Math.random()*a.Za);return a.isActive()||(f*=2),f*h}function lr(a,h){if(a.j.info("Error code "+h),h==2){var f=l(a.bb,a),m=a.Ua;const b=!m;m=new wn(m||"//www.google.com/images/cleardot.gif"),o.location&&o.location.protocol=="http"||oi(m,"https"),Bo(m),b?Z7(m.toString(),f):eg(m.toString(),f)}else at(2);a.I=0,a.l&&a.l.pa(h),Gh(a),Lh(a)}r.bb=function(a){a?(this.j.info("Successfully pinged google.com"),at(2)):(this.j.info("Failed to ping google.com"),at(1))};function Gh(a){if(a.I=0,a.ja=[],a.l){const h=wh(a.h);(h.length!=0||a.i.length!=0)&&(C(a.ja,h),C(a.ja,a.i),a.h.i.length=0,R(a.i),a.i.length=0),a.l.oa()}}function jh(a,h,f){var m=f instanceof wn?Ft(f):new wn(f);if(m.g!="")h&&(m.g=h+"."+m.g),ai(m,m.u);else{var b=o.location;m=b.protocol,h=h?h+"."+b.hostname:b.hostname,b=+b.port;const x=new wn(null);m&&oi(x,m),h&&(x.g=h),b&&ai(x,b),f&&(x.h=f),m=x}return f=a.G,h=a.wa,f&&h&&Ae(m,f,h),Ae(m,"VER",a.ka),pi(a,m),m}function zh(a,h,f){if(h&&!a.L)throw Error("Can't create secondary domain capable XhrIo object.");return h=a.Aa&&!a.ma?new Ve(new Xu({ab:f})):new Ve(a.ma),h.Fa(a.L),h}r.isActive=function(){return!!this.l&&this.l.isActive(this)};function Hh(){}r=Hh.prototype,r.ra=function(){},r.qa=function(){},r.pa=function(){},r.oa=function(){},r.isActive=function(){return!0},r.Ka=function(){};function Ho(){}Ho.prototype.g=function(a,h){return new mt(a,h)};function mt(a,h){Ye.call(this),this.g=new kh(h),this.l=a,this.h=h&&h.messageUrlParams||null,a=h&&h.messageHeaders||null,h&&h.clientProtocolHeaderRequired&&(a?a["X-Client-Protocol"]="webchannel":a={"X-Client-Protocol":"webchannel"}),this.g.o=a,a=h&&h.initMessageHeaders||null,h&&h.messageContentType&&(a?a["X-WebChannel-Content-Type"]=h.messageContentType:a={"X-WebChannel-Content-Type":h.messageContentType}),h&&h.sa&&(a?a["X-WebChannel-Client-Profile"]=h.sa:a={"X-WebChannel-Client-Profile":h.sa}),this.g.U=a,(a=h&&h.Qb)&&!I(a)&&(this.g.u=a),this.A=h&&h.supportsCrossDomainXhr||!1,this.v=h&&h.sendRawJson||!1,(h=h&&h.httpSessionIdParam)&&!I(h)&&(this.g.G=h,a=this.h,a!==null&&h in a&&(a=this.h,h in a&&delete a[h])),this.j=new Kr(this)}g(mt,Ye),mt.prototype.m=function(){this.g.l=this.j,this.A&&(this.g.L=!0),this.g.connect(this.l,this.h||void 0)},mt.prototype.close=function(){Zu(this.g)},mt.prototype.o=function(a){var h=this.g;if(typeof a=="string"){var f={};f.__data__=a,a=f}else this.v&&(f={},f.__data__=Bu(a),a=f);h.i.push(new z7(h.Ya++,a)),h.I==3&&jo(h)},mt.prototype.N=function(){this.g.l=null,delete this.j,Zu(this.g),delete this.g,mt.Z.N.call(this)};function Kh(a){qu.call(this),a.__headers__&&(this.headers=a.__headers__,this.statusCode=a.__status__,delete a.__headers__,delete a.__status__);var h=a.__sm__;if(h){e:{for(const f in h){a=f;break e}a=void 0}(this.i=a)&&(a=this.i,h=h!==null&&a in h?h[a]:void 0),this.data=h}else this.data=a}g(Kh,qu);function Wh(){$u.call(this),this.status=1}g(Wh,$u);function Kr(a){this.g=a}g(Kr,Hh),Kr.prototype.ra=function(){ot(this.g,"a")},Kr.prototype.qa=function(a){ot(this.g,new Kh(a))},Kr.prototype.pa=function(a){ot(this.g,new Wh)},Kr.prototype.oa=function(){ot(this.g,"b")},Ho.prototype.createWebChannel=Ho.prototype.g,mt.prototype.send=mt.prototype.o,mt.prototype.open=mt.prototype.m,mt.prototype.close=mt.prototype.close,H2=function(){return new Ho},z2=function(){return Mo()},j2=ar,Cc={jb:0,mb:1,nb:2,Hb:3,Mb:4,Jb:5,Kb:6,Ib:7,Gb:8,Lb:9,PROXY:10,NOPROXY:11,Eb:12,Ab:13,Bb:14,zb:15,Cb:16,Db:17,fb:18,eb:19,gb:20},Fo.NO_ERROR=0,Fo.TIMEOUT=8,Fo.HTTP_ERROR=6,oa=Fo,lh.COMPLETE="complete",G2=lh,ih.EventType=ei,ei.OPEN="a",ei.CLOSE="b",ei.ERROR="c",ei.MESSAGE="d",Ye.prototype.listen=Ye.prototype.J,wi=ih,Ve.prototype.listenOnce=Ve.prototype.K,Ve.prototype.getLastError=Ve.prototype.Ha,Ve.prototype.getLastErrorCode=Ve.prototype.ya,Ve.prototype.getStatus=Ve.prototype.ca,Ve.prototype.getResponseJson=Ve.prototype.La,Ve.prototype.getResponseText=Ve.prototype.la,Ve.prototype.send=Ve.prototype.ea,Ve.prototype.setWithCredentials=Ve.prototype.Fa,$2=Ve}).apply(typeof Wo<"u"?Wo:typeof self<"u"?self:typeof window<"u"?window:{});/*!
 * re2js
 * RE2JS is the JavaScript port of RE2, a regular expression engine that provides linear time matching
 *
 * @version v0.4.3
 * @author Alexey Vasiliev
 * @homepage https://github.com/le0pard/re2js#readme
 * @repository github:le0pard/re2js
 * @license MIT
 */const Ne=class Ne{};_(Ne,"FOLD_CASE",1),_(Ne,"LITERAL",2),_(Ne,"CLASS_NL",4),_(Ne,"DOT_NL",8),_(Ne,"ONE_LINE",16),_(Ne,"NON_GREEDY",32),_(Ne,"PERL_X",64),_(Ne,"UNICODE_GROUPS",128),_(Ne,"WAS_DOLLAR",256),_(Ne,"MATCH_NL",Ne.CLASS_NL|Ne.DOT_NL),_(Ne,"PERL",Ne.CLASS_NL|Ne.ONE_LINE|Ne.PERL_X|Ne.UNICODE_GROUPS),_(Ne,"POSIX",0),_(Ne,"UNANCHORED",0),_(Ne,"ANCHOR_START",1),_(Ne,"ANCHOR_BOTH",2);let z=Ne;class V{static toUpperCase(e){const t=String.fromCodePoint(e).toUpperCase();if(t.length>1)return e;const n=String.fromCodePoint(t.codePointAt(0)).toLowerCase();return n.length>1||n.codePointAt(0)!==e?e:t.codePointAt(0)}static toLowerCase(e){const t=String.fromCodePoint(e).toLowerCase();if(t.length>1)return e;const n=String.fromCodePoint(t.codePointAt(0)).toUpperCase();return n.length>1||n.codePointAt(0)!==e?e:t.codePointAt(0)}}_(V,"CODES",new Map([["\x07",7],["\b",8],["	",9],[`
`,10],["\v",11],["\f",12],["\r",13],[" ",32],['"',34],["$",36],["&",38],["(",40],[")",41],["*",42],["+",43],["-",45],[".",46],["0",48],["1",49],["2",50],["3",51],["4",52],["5",53],["6",54],["7",55],["8",56],["9",57],[":",58],["<",60],[">",62],["?",63],["A",65],["B",66],["C",67],["F",70],["P",80],["Q",81],["U",85],["Z",90],["[",91],["\\",92],["]",93],["^",94],["_",95],["a",97],["b",98],["f",102],["i",105],["m",109],["n",110],["r",114],["s",115],["t",116],["v",118],["x",120],["z",122],["{",123],["|",124],["}",125]]));const p=class p{};_(p,"CASE_ORBIT",new Map([[75,107],[107,8490],[8490,75],[83,115],[115,383],[383,83],[181,924],[924,956],[956,181],[197,229],[229,8491],[8491,197],[452,453],[453,454],[454,452],[455,456],[456,457],[457,455],[458,459],[459,460],[460,458],[497,498],[498,499],[499,497],[837,921],[921,953],[953,8126],[8126,837],[914,946],[946,976],[976,914],[917,949],[949,1013],[1013,917],[920,952],[952,977],[977,1012],[1012,920],[922,954],[954,1008],[1008,922],[928,960],[960,982],[982,928],[929,961],[961,1009],[1009,929],[931,962],[962,963],[963,931],[934,966],[966,981],[981,934],[937,969],[969,8486],[8486,937],[1042,1074],[1074,7296],[7296,1042],[1044,1076],[1076,7297],[7297,1044],[1054,1086],[1086,7298],[7298,1054],[1057,1089],[1089,7299],[7299,1057],[1058,1090],[1090,7300],[7300,7301],[7301,1058],[1066,1098],[1098,7302],[7302,1066],[1122,1123],[1123,7303],[7303,1122],[7304,42570],[42570,42571],[42571,7304],[7776,7777],[7777,7835],[7835,7776],[223,7838],[7838,223],[8064,8072],[8072,8064],[8065,8073],[8073,8065],[8066,8074],[8074,8066],[8067,8075],[8075,8067],[8068,8076],[8076,8068],[8069,8077],[8077,8069],[8070,8078],[8078,8070],[8071,8079],[8079,8071],[8080,8088],[8088,8080],[8081,8089],[8089,8081],[8082,8090],[8090,8082],[8083,8091],[8091,8083],[8084,8092],[8092,8084],[8085,8093],[8093,8085],[8086,8094],[8094,8086],[8087,8095],[8095,8087],[8096,8104],[8104,8096],[8097,8105],[8105,8097],[8098,8106],[8106,8098],[8099,8107],[8107,8099],[8100,8108],[8108,8100],[8101,8109],[8109,8101],[8102,8110],[8110,8102],[8103,8111],[8111,8103],[8115,8124],[8124,8115],[8131,8140],[8140,8131],[912,8147],[8147,912],[944,8163],[8163,944],[8179,8188],[8188,8179],[64261,64262],[64262,64261],[66560,66600],[66600,66560],[66561,66601],[66601,66561],[66562,66602],[66602,66562],[66563,66603],[66603,66563],[66564,66604],[66604,66564],[66565,66605],[66605,66565],[66566,66606],[66606,66566],[66567,66607],[66607,66567],[66568,66608],[66608,66568],[66569,66609],[66609,66569],[66570,66610],[66610,66570],[66571,66611],[66611,66571],[66572,66612],[66612,66572],[66573,66613],[66613,66573],[66574,66614],[66614,66574],[66575,66615],[66615,66575],[66576,66616],[66616,66576],[66577,66617],[66617,66577],[66578,66618],[66618,66578],[66579,66619],[66619,66579],[66580,66620],[66620,66580],[66581,66621],[66621,66581],[66582,66622],[66622,66582],[66583,66623],[66623,66583],[66584,66624],[66624,66584],[66585,66625],[66625,66585],[66586,66626],[66626,66586],[66587,66627],[66627,66587],[66588,66628],[66628,66588],[66589,66629],[66629,66589],[66590,66630],[66630,66590],[66591,66631],[66631,66591],[66592,66632],[66632,66592],[66593,66633],[66633,66593],[66594,66634],[66634,66594],[66595,66635],[66635,66595],[66596,66636],[66636,66596],[66597,66637],[66637,66597],[66598,66638],[66638,66598],[66599,66639],[66639,66599],[66736,66776],[66776,66736],[66737,66777],[66777,66737],[66738,66778],[66778,66738],[66739,66779],[66779,66739],[66740,66780],[66780,66740],[66741,66781],[66781,66741],[66742,66782],[66782,66742],[66743,66783],[66783,66743],[66744,66784],[66784,66744],[66745,66785],[66785,66745],[66746,66786],[66786,66746],[66747,66787],[66787,66747],[66748,66788],[66788,66748],[66749,66789],[66789,66749],[66750,66790],[66790,66750],[66751,66791],[66791,66751],[66752,66792],[66792,66752],[66753,66793],[66793,66753],[66754,66794],[66794,66754],[66755,66795],[66795,66755],[66756,66796],[66796,66756],[66757,66797],[66797,66757],[66758,66798],[66798,66758],[66759,66799],[66799,66759],[66760,66800],[66800,66760],[66761,66801],[66801,66761],[66762,66802],[66802,66762],[66763,66803],[66803,66763],[66764,66804],[66804,66764],[66765,66805],[66805,66765],[66766,66806],[66806,66766],[66767,66807],[66807,66767],[66768,66808],[66808,66768],[66769,66809],[66809,66769],[66770,66810],[66810,66770],[66771,66811],[66811,66771],[66928,66967],[66967,66928],[66929,66968],[66968,66929],[66930,66969],[66969,66930],[66931,66970],[66970,66931],[66932,66971],[66971,66932],[66933,66972],[66972,66933],[66934,66973],[66973,66934],[66935,66974],[66974,66935],[66936,66975],[66975,66936],[66937,66976],[66976,66937],[66938,66977],[66977,66938],[66940,66979],[66979,66940],[66941,66980],[66980,66941],[66942,66981],[66981,66942],[66943,66982],[66982,66943],[66944,66983],[66983,66944],[66945,66984],[66984,66945],[66946,66985],[66985,66946],[66947,66986],[66986,66947],[66948,66987],[66987,66948],[66949,66988],[66988,66949],[66950,66989],[66989,66950],[66951,66990],[66990,66951],[66952,66991],[66991,66952],[66953,66992],[66992,66953],[66954,66993],[66993,66954],[66956,66995],[66995,66956],[66957,66996],[66996,66957],[66958,66997],[66997,66958],[66959,66998],[66998,66959],[66960,66999],[66999,66960],[66961,67e3],[67e3,66961],[66962,67001],[67001,66962],[66964,67003],[67003,66964],[66965,67004],[67004,66965],[68736,68800],[68800,68736],[68737,68801],[68801,68737],[68738,68802],[68802,68738],[68739,68803],[68803,68739],[68740,68804],[68804,68740],[68741,68805],[68805,68741],[68742,68806],[68806,68742],[68743,68807],[68807,68743],[68744,68808],[68808,68744],[68745,68809],[68809,68745],[68746,68810],[68810,68746],[68747,68811],[68811,68747],[68748,68812],[68812,68748],[68749,68813],[68813,68749],[68750,68814],[68814,68750],[68751,68815],[68815,68751],[68752,68816],[68816,68752],[68753,68817],[68817,68753],[68754,68818],[68818,68754],[68755,68819],[68819,68755],[68756,68820],[68820,68756],[68757,68821],[68821,68757],[68758,68822],[68822,68758],[68759,68823],[68823,68759],[68760,68824],[68824,68760],[68761,68825],[68825,68761],[68762,68826],[68826,68762],[68763,68827],[68827,68763],[68764,68828],[68828,68764],[68765,68829],[68829,68765],[68766,68830],[68830,68766],[68767,68831],[68831,68767],[68768,68832],[68832,68768],[68769,68833],[68833,68769],[68770,68834],[68834,68770],[68771,68835],[68835,68771],[68772,68836],[68836,68772],[68773,68837],[68837,68773],[68774,68838],[68838,68774],[68775,68839],[68839,68775],[68776,68840],[68840,68776],[68777,68841],[68841,68777],[68778,68842],[68842,68778],[68779,68843],[68843,68779],[68780,68844],[68844,68780],[68781,68845],[68845,68781],[68782,68846],[68846,68782],[68783,68847],[68847,68783],[68784,68848],[68848,68784],[68785,68849],[68849,68785],[68786,68850],[68850,68786],[71840,71872],[71872,71840],[71841,71873],[71873,71841],[71842,71874],[71874,71842],[71843,71875],[71875,71843],[71844,71876],[71876,71844],[71845,71877],[71877,71845],[71846,71878],[71878,71846],[71847,71879],[71879,71847],[71848,71880],[71880,71848],[71849,71881],[71881,71849],[71850,71882],[71882,71850],[71851,71883],[71883,71851],[71852,71884],[71884,71852],[71853,71885],[71885,71853],[71854,71886],[71886,71854],[71855,71887],[71887,71855],[71856,71888],[71888,71856],[71857,71889],[71889,71857],[71858,71890],[71890,71858],[71859,71891],[71891,71859],[71860,71892],[71892,71860],[71861,71893],[71893,71861],[71862,71894],[71894,71862],[71863,71895],[71895,71863],[71864,71896],[71896,71864],[71865,71897],[71897,71865],[71866,71898],[71898,71866],[71867,71899],[71899,71867],[71868,71900],[71900,71868],[71869,71901],[71901,71869],[71870,71902],[71902,71870],[71871,71903],[71903,71871],[93760,93792],[93792,93760],[93761,93793],[93793,93761],[93762,93794],[93794,93762],[93763,93795],[93795,93763],[93764,93796],[93796,93764],[93765,93797],[93797,93765],[93766,93798],[93798,93766],[93767,93799],[93799,93767],[93768,93800],[93800,93768],[93769,93801],[93801,93769],[93770,93802],[93802,93770],[93771,93803],[93803,93771],[93772,93804],[93804,93772],[93773,93805],[93805,93773],[93774,93806],[93806,93774],[93775,93807],[93807,93775],[93776,93808],[93808,93776],[93777,93809],[93809,93777],[93778,93810],[93810,93778],[93779,93811],[93811,93779],[93780,93812],[93812,93780],[93781,93813],[93813,93781],[93782,93814],[93814,93782],[93783,93815],[93815,93783],[93784,93816],[93816,93784],[93785,93817],[93817,93785],[93786,93818],[93818,93786],[93787,93819],[93819,93787],[93788,93820],[93820,93788],[93789,93821],[93821,93789],[93790,93822],[93822,93790],[93791,93823],[93823,93791],[125184,125218],[125218,125184],[125185,125219],[125219,125185],[125186,125220],[125220,125186],[125187,125221],[125221,125187],[125188,125222],[125222,125188],[125189,125223],[125223,125189],[125190,125224],[125224,125190],[125191,125225],[125225,125191],[125192,125226],[125226,125192],[125193,125227],[125227,125193],[125194,125228],[125228,125194],[125195,125229],[125229,125195],[125196,125230],[125230,125196],[125197,125231],[125231,125197],[125198,125232],[125232,125198],[125199,125233],[125233,125199],[125200,125234],[125234,125200],[125201,125235],[125235,125201],[125202,125236],[125236,125202],[125203,125237],[125237,125203],[125204,125238],[125238,125204],[125205,125239],[125239,125205],[125206,125240],[125240,125206],[125207,125241],[125241,125207],[125208,125242],[125242,125208],[125209,125243],[125243,125209],[125210,125244],[125244,125210],[125211,125245],[125245,125211],[125212,125246],[125246,125212],[125213,125247],[125247,125213],[125214,125248],[125248,125214],[125215,125249],[125249,125215],[125216,125250],[125250,125216],[125217,125251],[125251,125217]])),_(p,"C",[[0,31,1],[127,159,1],[173,888,715],[889,896,7],[897,899,1],[907,909,2],[930,1328,398],[1367,1368,1],[1419,1420,1],[1424,1480,56],[1481,1487,1],[1515,1518,1],[1525,1541,1],[1564,1757,193],[1806,1807,1],[1867,1868,1],[1970,1983,1],[2043,2044,1],[2094,2095,1],[2111,2140,29],[2141,2143,2],[2155,2159,1],[2191,2199,1],[2274,2436,162],[2445,2446,1],[2449,2450,1],[2473,2481,8],[2483,2485,1],[2490,2491,1],[2501,2502,1],[2505,2506,1],[2511,2518,1],[2520,2523,1],[2526,2532,6],[2533,2559,26],[2560,2564,4],[2571,2574,1],[2577,2578,1],[2601,2609,8],[2612,2618,3],[2619,2621,2],[2627,2630,1],[2633,2634,1],[2638,2640,1],[2642,2648,1],[2653,2655,2],[2656,2661,1],[2679,2688,1],[2692,2702,10],[2706,2729,23],[2737,2740,3],[2746,2747,1],[2758,2766,4],[2767,2769,2],[2770,2783,1],[2788,2789,1],[2802,2808,1],[2816,2820,4],[2829,2830,1],[2833,2834,1],[2857,2865,8],[2868,2874,6],[2875,2885,10],[2886,2889,3],[2890,2894,4],[2895,2900,1],[2904,2907,1],[2910,2916,6],[2917,2936,19],[2937,2945,1],[2948,2955,7],[2956,2957,1],[2961,2966,5],[2967,2968,1],[2971,2973,2],[2976,2978,1],[2981,2983,1],[2987,2989,1],[3002,3005,1],[3011,3013,1],[3017,3022,5],[3023,3025,2],[3026,3030,1],[3032,3045,1],[3067,3071,1],[3085,3089,4],[3113,3130,17],[3131,3141,10],[3145,3150,5],[3151,3156,1],[3159,3163,4],[3164,3166,2],[3167,3172,5],[3173,3184,11],[3185,3190,1],[3213,3217,4],[3241,3252,11],[3258,3259,1],[3269,3273,4],[3278,3284,1],[3287,3292,1],[3295,3300,5],[3301,3312,11],[3316,3327,1],[3341,3345,4],[3397,3401,4],[3408,3411,1],[3428,3429,1],[3456,3460,4],[3479,3481,1],[3506,3516,10],[3518,3519,1],[3527,3529,1],[3531,3534,1],[3541,3543,2],[3552,3557,1],[3568,3569,1],[3573,3584,1],[3643,3646,1],[3676,3712,1],[3715,3717,2],[3723,3748,25],[3750,3774,24],[3775,3781,6],[3783,3791,8],[3802,3803,1],[3808,3839,1],[3912,3949,37],[3950,3952,1],[3992,4029,37],[4045,4059,14],[4060,4095,1],[4294,4296,2],[4297,4300,1],[4302,4303,1],[4681,4686,5],[4687,4695,8],[4697,4702,5],[4703,4745,42],[4750,4751,1],[4785,4790,5],[4791,4799,8],[4801,4806,5],[4807,4823,16],[4881,4886,5],[4887,4955,68],[4956,4989,33],[4990,4991,1],[5018,5023,1],[5110,5111,1],[5118,5119,1],[5789,5791,1],[5881,5887,1],[5910,5918,1],[5943,5951,1],[5972,5983,1],[5997,6001,4],[6004,6015,1],[6110,6111,1],[6122,6127,1],[6138,6143,1],[6158,6170,12],[6171,6175,1],[6265,6271,1],[6315,6319,1],[6390,6399,1],[6431,6444,13],[6445,6447,1],[6460,6463,1],[6465,6467,1],[6510,6511,1],[6517,6527,1],[6572,6575,1],[6602,6607,1],[6619,6621,1],[6684,6685,1],[6751,6781,30],[6782,6794,12],[6795,6799,1],[6810,6815,1],[6830,6831,1],[6863,6911,1],[6989,6991,1],[7039,7156,117],[7157,7163,1],[7224,7226,1],[7242,7244,1],[7305,7311,1],[7355,7356,1],[7368,7375,1],[7419,7423,1],[7958,7959,1],[7966,7967,1],[8006,8007,1],[8014,8015,1],[8024,8030,2],[8062,8063,1],[8117,8133,16],[8148,8149,1],[8156,8176,20],[8177,8181,4],[8191,8203,12],[8204,8207,1],[8234,8238,1],[8288,8303,1],[8306,8307,1],[8335,8349,14],[8350,8351,1],[8385,8399,1],[8433,8447,1],[8588,8591,1],[9255,9279,1],[9291,9311,1],[11124,11125,1],[11158,11508,350],[11509,11512,1],[11558,11560,2],[11561,11564,1],[11566,11567,1],[11624,11630,1],[11633,11646,1],[11671,11679,1],[11687,11743,8],[11870,11903,1],[11930,12020,90],[12021,12031,1],[12246,12271,1],[12352,12439,87],[12440,12544,104],[12545,12548,1],[12592,12687,95],[12772,12782,1],[12831,42125,29294],[42126,42127,1],[42183,42191,1],[42540,42559,1],[42744,42751,1],[42955,42959,1],[42962,42964,2],[42970,42993,1],[43053,43055,1],[43066,43071,1],[43128,43135,1],[43206,43213,1],[43226,43231,1],[43348,43358,1],[43389,43391,1],[43470,43482,12],[43483,43485,1],[43519,43575,56],[43576,43583,1],[43598,43599,1],[43610,43611,1],[43715,43738,1],[43767,43776,1],[43783,43784,1],[43791,43792,1],[43799,43807,1],[43815,43823,8],[43884,43887,1],[44014,44015,1],[44026,44031,1],[55204,55215,1],[55239,55242,1],[55292,63743,1],[64110,64111,1],[64218,64255,1],[64263,64274,1],[64280,64284,1],[64311,64317,6],[64319,64325,3],[64451,64466,1],[64912,64913,1],[64968,64974,1],[64976,65007,1],[65050,65055,1],[65107,65127,20],[65132,65135,1],[65141,65277,136],[65278,65280,1],[65471,65473,1],[65480,65481,1],[65488,65489,1],[65496,65497,1],[65501,65503,1],[65511,65519,8],[65520,65531,1],[65534,65535,1],[65548,65575,27],[65595,65598,3],[65614,65615,1],[65630,65663,1],[65787,65791,1],[65795,65798,1],[65844,65846,1],[65935,65949,14],[65950,65951,1],[65953,65999,1],[66046,66175,1],[66205,66207,1],[66257,66271,1],[66300,66303,1],[66340,66348,1],[66379,66383,1],[66427,66431,1],[66462,66500,38],[66501,66503,1],[66518,66559,1],[66718,66719,1],[66730,66735,1],[66772,66775,1],[66812,66815,1],[66856,66863,1],[66916,66926,1],[66939,66955,16],[66963,66966,3],[66978,66994,16],[67002,67005,3],[67006,67071,1],[67383,67391,1],[67414,67423,1],[67432,67455,1],[67462,67505,43],[67515,67583,1],[67590,67591,1],[67593,67638,45],[67641,67643,1],[67645,67646,1],[67670,67743,73],[67744,67750,1],[67760,67807,1],[67827,67830,3],[67831,67834,1],[67868,67870,1],[67898,67902,1],[67904,67967,1],[68024,68027,1],[68048,68049,1],[68100,68103,3],[68104,68107,1],[68116,68120,4],[68150,68151,1],[68155,68158,1],[68169,68175,1],[68185,68191,1],[68256,68287,1],[68327,68330,1],[68343,68351,1],[68406,68408,1],[68438,68439,1],[68467,68471,1],[68498,68504,1],[68509,68520,1],[68528,68607,1],[68681,68735,1],[68787,68799,1],[68851,68857,1],[68904,68911,1],[68922,69215,1],[69247,69290,43],[69294,69295,1],[69298,69372,1],[69416,69423,1],[69466,69487,1],[69514,69551,1],[69580,69599,1],[69623,69631,1],[69710,69713,1],[69750,69758,1],[69821,69827,6],[69828,69839,1],[69865,69871,1],[69882,69887,1],[69941,69960,19],[69961,69967,1],[70007,70015,1],[70112,70133,21],[70134,70143,1],[70162,70210,48],[70211,70271,1],[70279,70281,2],[70286,70302,16],[70314,70319,1],[70379,70383,1],[70394,70399,1],[70404,70413,9],[70414,70417,3],[70418,70441,23],[70449,70452,3],[70458,70469,11],[70470,70473,3],[70474,70478,4],[70479,70481,2],[70482,70486,1],[70488,70492,1],[70500,70501,1],[70509,70511,1],[70517,70655,1],[70748,70754,6],[70755,70783,1],[70856,70863,1],[70874,71039,1],[71094,71095,1],[71134,71167,1],[71237,71247,1],[71258,71263,1],[71277,71295,1],[71354,71359,1],[71370,71423,1],[71451,71452,1],[71468,71471,1],[71495,71679,1],[71740,71839,1],[71923,71934,1],[71943,71944,1],[71946,71947,1],[71956,71959,3],[71990,71993,3],[71994,72007,13],[72008,72015,1],[72026,72095,1],[72104,72105,1],[72152,72153,1],[72165,72191,1],[72264,72271,1],[72355,72367,1],[72441,72447,1],[72458,72703,1],[72713,72759,46],[72774,72783,1],[72813,72815,1],[72848,72849,1],[72872,72887,15],[72888,72959,1],[72967,72970,3],[73015,73017,1],[73019,73022,3],[73032,73039,1],[73050,73055,1],[73062,73065,3],[73103,73106,3],[73113,73119,1],[73130,73439,1],[73465,73471,1],[73489,73531,42],[73532,73533,1],[73562,73647,1],[73649,73663,1],[73714,73726,1],[74650,74751,1],[74863,74869,6],[74870,74879,1],[75076,77711,1],[77811,77823,1],[78896,78911,1],[78934,82943,1],[83527,92159,1],[92729,92735,1],[92767,92778,11],[92779,92781,1],[92863,92874,11],[92875,92879,1],[92910,92911,1],[92918,92927,1],[92998,93007,1],[93018,93026,8],[93048,93052,1],[93072,93759,1],[93851,93951,1],[94027,94030,1],[94088,94094,1],[94112,94175,1],[94181,94191,1],[94194,94207,1],[100344,100351,1],[101590,101631,1],[101641,110575,1],[110580,110588,8],[110591,110883,292],[110884,110897,1],[110899,110927,1],[110931,110932,1],[110934,110947,1],[110952,110959,1],[111356,113663,1],[113771,113775,1],[113789,113791,1],[113801,113807,1],[113818,113819,1],[113824,118527,1],[118574,118575,1],[118599,118607,1],[118724,118783,1],[119030,119039,1],[119079,119080,1],[119155,119162,1],[119275,119295,1],[119366,119487,1],[119508,119519,1],[119540,119551,1],[119639,119647,1],[119673,119807,1],[119893,119965,72],[119968,119969,1],[119971,119972,1],[119975,119976,1],[119981,119994,13],[119996,120004,8],[120070,120075,5],[120076,120085,9],[120093,120122,29],[120127,120133,6],[120135,120137,1],[120145,120486,341],[120487,120780,293],[120781,121484,703],[121485,121498,1],[121504,121520,16],[121521,122623,1],[122655,122660,1],[122667,122879,1],[122887,122905,18],[122906,122914,8],[122917,122923,6],[122924,122927,1],[122990,123022,1],[123024,123135,1],[123181,123183,1],[123198,123199,1],[123210,123213,1],[123216,123535,1],[123567,123583,1],[123642,123646,1],[123648,124111,1],[124154,124895,1],[124903,124908,5],[124911,124927,16],[125125,125126,1],[125143,125183,1],[125260,125263,1],[125274,125277,1],[125280,126064,1],[126133,126208,1],[126270,126463,1],[126468,126496,28],[126499,126501,2],[126502,126504,2],[126515,126520,5],[126522,126524,2],[126525,126529,1],[126531,126534,1],[126536,126540,2],[126544,126547,3],[126549,126550,1],[126552,126560,2],[126563,126565,2],[126566,126571,5],[126579,126589,5],[126591,126602,11],[126620,126624,1],[126628,126634,6],[126652,126703,1],[126706,126975,1],[127020,127023,1],[127124,127135,1],[127151,127152,1],[127168,127184,16],[127222,127231,1],[127406,127461,1],[127491,127503,1],[127548,127551,1],[127561,127567,1],[127570,127583,1],[127590,127743,1],[128728,128731,1],[128749,128751,1],[128765,128767,1],[128887,128890,1],[128986,128991,1],[129004,129007,1],[129009,129023,1],[129036,129039,1],[129096,129103,1],[129114,129119,1],[129160,129167,1],[129198,129199,1],[129202,129279,1],[129620,129631,1],[129646,129647,1],[129661,129663,1],[129673,129679,1],[129726,129734,8],[129735,129741,1],[129756,129759,1],[129769,129775,1],[129785,129791,1],[129939,129995,56],[129996,130031,1],[130042,131071,1],[173792,173823,1],[177978,177983,1],[178206,178207,1],[183970,183983,1],[191457,191471,1],[192094,194559,1],[195102,196607,1],[201547,201551,1],[205744,917759,1],[918e3,1114111,1]]),_(p,"Cc",[[0,31,1],[127,159,1]]),_(p,"Cf",[[173,1536,1363],[1537,1541,1],[1564,1757,193],[1807,2192,385],[2193,2274,81],[6158,8203,2045],[8204,8207,1],[8234,8238,1],[8288,8292,1],[8294,8303,1],[65279,65529,250],[65530,65531,1],[69821,69837,16],[78896,78911,1],[113824,113827,1],[119155,119162,1],[917505,917536,31],[917537,917631,1]]),_(p,"Co",[[57344,63743,1],[983040,1048573,1],[1048576,1114109,1]]),_(p,"Cs",[[55296,57343,1]]),_(p,"L",[[65,90,1],[97,122,1],[170,181,11],[186,192,6],[193,214,1],[216,246,1],[248,705,1],[710,721,1],[736,740,1],[748,750,2],[880,884,1],[886,887,1],[890,893,1],[895,902,7],[904,906,1],[908,910,2],[911,929,1],[931,1013,1],[1015,1153,1],[1162,1327,1],[1329,1366,1],[1369,1376,7],[1377,1416,1],[1488,1514,1],[1519,1522,1],[1568,1610,1],[1646,1647,1],[1649,1747,1],[1749,1765,16],[1766,1774,8],[1775,1786,11],[1787,1788,1],[1791,1808,17],[1810,1839,1],[1869,1957,1],[1969,1994,25],[1995,2026,1],[2036,2037,1],[2042,2048,6],[2049,2069,1],[2074,2084,10],[2088,2112,24],[2113,2136,1],[2144,2154,1],[2160,2183,1],[2185,2190,1],[2208,2249,1],[2308,2361,1],[2365,2384,19],[2392,2401,1],[2417,2432,1],[2437,2444,1],[2447,2448,1],[2451,2472,1],[2474,2480,1],[2482,2486,4],[2487,2489,1],[2493,2510,17],[2524,2525,1],[2527,2529,1],[2544,2545,1],[2556,2565,9],[2566,2570,1],[2575,2576,1],[2579,2600,1],[2602,2608,1],[2610,2611,1],[2613,2614,1],[2616,2617,1],[2649,2652,1],[2654,2674,20],[2675,2676,1],[2693,2701,1],[2703,2705,1],[2707,2728,1],[2730,2736,1],[2738,2739,1],[2741,2745,1],[2749,2768,19],[2784,2785,1],[2809,2821,12],[2822,2828,1],[2831,2832,1],[2835,2856,1],[2858,2864,1],[2866,2867,1],[2869,2873,1],[2877,2908,31],[2909,2911,2],[2912,2913,1],[2929,2947,18],[2949,2954,1],[2958,2960,1],[2962,2965,1],[2969,2970,1],[2972,2974,2],[2975,2979,4],[2980,2984,4],[2985,2986,1],[2990,3001,1],[3024,3077,53],[3078,3084,1],[3086,3088,1],[3090,3112,1],[3114,3129,1],[3133,3160,27],[3161,3162,1],[3165,3168,3],[3169,3200,31],[3205,3212,1],[3214,3216,1],[3218,3240,1],[3242,3251,1],[3253,3257,1],[3261,3293,32],[3294,3296,2],[3297,3313,16],[3314,3332,18],[3333,3340,1],[3342,3344,1],[3346,3386,1],[3389,3406,17],[3412,3414,1],[3423,3425,1],[3450,3455,1],[3461,3478,1],[3482,3505,1],[3507,3515,1],[3517,3520,3],[3521,3526,1],[3585,3632,1],[3634,3635,1],[3648,3654,1],[3713,3714,1],[3716,3718,2],[3719,3722,1],[3724,3747,1],[3749,3751,2],[3752,3760,1],[3762,3763,1],[3773,3776,3],[3777,3780,1],[3782,3804,22],[3805,3807,1],[3840,3904,64],[3905,3911,1],[3913,3948,1],[3976,3980,1],[4096,4138,1],[4159,4176,17],[4177,4181,1],[4186,4189,1],[4193,4197,4],[4198,4206,8],[4207,4208,1],[4213,4225,1],[4238,4256,18],[4257,4293,1],[4295,4301,6],[4304,4346,1],[4348,4680,1],[4682,4685,1],[4688,4694,1],[4696,4698,2],[4699,4701,1],[4704,4744,1],[4746,4749,1],[4752,4784,1],[4786,4789,1],[4792,4798,1],[4800,4802,2],[4803,4805,1],[4808,4822,1],[4824,4880,1],[4882,4885,1],[4888,4954,1],[4992,5007,1],[5024,5109,1],[5112,5117,1],[5121,5740,1],[5743,5759,1],[5761,5786,1],[5792,5866,1],[5873,5880,1],[5888,5905,1],[5919,5937,1],[5952,5969,1],[5984,5996,1],[5998,6e3,1],[6016,6067,1],[6103,6108,5],[6176,6264,1],[6272,6276,1],[6279,6312,1],[6314,6320,6],[6321,6389,1],[6400,6430,1],[6480,6509,1],[6512,6516,1],[6528,6571,1],[6576,6601,1],[6656,6678,1],[6688,6740,1],[6823,6917,94],[6918,6963,1],[6981,6988,1],[7043,7072,1],[7086,7087,1],[7098,7141,1],[7168,7203,1],[7245,7247,1],[7258,7293,1],[7296,7304,1],[7312,7354,1],[7357,7359,1],[7401,7404,1],[7406,7411,1],[7413,7414,1],[7418,7424,6],[7425,7615,1],[7680,7957,1],[7960,7965,1],[7968,8005,1],[8008,8013,1],[8016,8023,1],[8025,8031,2],[8032,8061,1],[8064,8116,1],[8118,8124,1],[8126,8130,4],[8131,8132,1],[8134,8140,1],[8144,8147,1],[8150,8155,1],[8160,8172,1],[8178,8180,1],[8182,8188,1],[8305,8319,14],[8336,8348,1],[8450,8455,5],[8458,8467,1],[8469,8473,4],[8474,8477,1],[8484,8490,2],[8491,8493,1],[8495,8505,1],[8508,8511,1],[8517,8521,1],[8526,8579,53],[8580,11264,2684],[11265,11492,1],[11499,11502,1],[11506,11507,1],[11520,11557,1],[11559,11565,6],[11568,11623,1],[11631,11648,17],[11649,11670,1],[11680,11686,1],[11688,11694,1],[11696,11702,1],[11704,11710,1],[11712,11718,1],[11720,11726,1],[11728,11734,1],[11736,11742,1],[11823,12293,470],[12294,12337,43],[12338,12341,1],[12347,12348,1],[12353,12438,1],[12445,12447,1],[12449,12538,1],[12540,12543,1],[12549,12591,1],[12593,12686,1],[12704,12735,1],[12784,12799,1],[13312,19903,1],[19968,42124,1],[42192,42237,1],[42240,42508,1],[42512,42527,1],[42538,42539,1],[42560,42606,1],[42623,42653,1],[42656,42725,1],[42775,42783,1],[42786,42888,1],[42891,42954,1],[42960,42961,1],[42963,42965,2],[42966,42969,1],[42994,43009,1],[43011,43013,1],[43015,43018,1],[43020,43042,1],[43072,43123,1],[43138,43187,1],[43250,43255,1],[43259,43261,2],[43262,43274,12],[43275,43301,1],[43312,43334,1],[43360,43388,1],[43396,43442,1],[43471,43488,17],[43489,43492,1],[43494,43503,1],[43514,43518,1],[43520,43560,1],[43584,43586,1],[43588,43595,1],[43616,43638,1],[43642,43646,4],[43647,43695,1],[43697,43701,4],[43702,43705,3],[43706,43709,1],[43712,43714,2],[43739,43741,1],[43744,43754,1],[43762,43764,1],[43777,43782,1],[43785,43790,1],[43793,43798,1],[43808,43814,1],[43816,43822,1],[43824,43866,1],[43868,43881,1],[43888,44002,1],[44032,55203,1],[55216,55238,1],[55243,55291,1],[63744,64109,1],[64112,64217,1],[64256,64262,1],[64275,64279,1],[64285,64287,2],[64288,64296,1],[64298,64310,1],[64312,64316,1],[64318,64320,2],[64321,64323,2],[64324,64326,2],[64327,64433,1],[64467,64829,1],[64848,64911,1],[64914,64967,1],[65008,65019,1],[65136,65140,1],[65142,65276,1],[65313,65338,1],[65345,65370,1],[65382,65470,1],[65474,65479,1],[65482,65487,1],[65490,65495,1],[65498,65500,1],[65536,65547,1],[65549,65574,1],[65576,65594,1],[65596,65597,1],[65599,65613,1],[65616,65629,1],[65664,65786,1],[66176,66204,1],[66208,66256,1],[66304,66335,1],[66349,66368,1],[66370,66377,1],[66384,66421,1],[66432,66461,1],[66464,66499,1],[66504,66511,1],[66560,66717,1],[66736,66771,1],[66776,66811,1],[66816,66855,1],[66864,66915,1],[66928,66938,1],[66940,66954,1],[66956,66962,1],[66964,66965,1],[66967,66977,1],[66979,66993,1],[66995,67001,1],[67003,67004,1],[67072,67382,1],[67392,67413,1],[67424,67431,1],[67456,67461,1],[67463,67504,1],[67506,67514,1],[67584,67589,1],[67592,67594,2],[67595,67637,1],[67639,67640,1],[67644,67647,3],[67648,67669,1],[67680,67702,1],[67712,67742,1],[67808,67826,1],[67828,67829,1],[67840,67861,1],[67872,67897,1],[67968,68023,1],[68030,68031,1],[68096,68112,16],[68113,68115,1],[68117,68119,1],[68121,68149,1],[68192,68220,1],[68224,68252,1],[68288,68295,1],[68297,68324,1],[68352,68405,1],[68416,68437,1],[68448,68466,1],[68480,68497,1],[68608,68680,1],[68736,68786,1],[68800,68850,1],[68864,68899,1],[69248,69289,1],[69296,69297,1],[69376,69404,1],[69415,69424,9],[69425,69445,1],[69488,69505,1],[69552,69572,1],[69600,69622,1],[69635,69687,1],[69745,69746,1],[69749,69763,14],[69764,69807,1],[69840,69864,1],[69891,69926,1],[69956,69959,3],[69968,70002,1],[70006,70019,13],[70020,70066,1],[70081,70084,1],[70106,70108,2],[70144,70161,1],[70163,70187,1],[70207,70208,1],[70272,70278,1],[70280,70282,2],[70283,70285,1],[70287,70301,1],[70303,70312,1],[70320,70366,1],[70405,70412,1],[70415,70416,1],[70419,70440,1],[70442,70448,1],[70450,70451,1],[70453,70457,1],[70461,70480,19],[70493,70497,1],[70656,70708,1],[70727,70730,1],[70751,70753,1],[70784,70831,1],[70852,70853,1],[70855,71040,185],[71041,71086,1],[71128,71131,1],[71168,71215,1],[71236,71296,60],[71297,71338,1],[71352,71424,72],[71425,71450,1],[71488,71494,1],[71680,71723,1],[71840,71903,1],[71935,71942,1],[71945,71948,3],[71949,71955,1],[71957,71958,1],[71960,71983,1],[71999,72001,2],[72096,72103,1],[72106,72144,1],[72161,72163,2],[72192,72203,11],[72204,72242,1],[72250,72272,22],[72284,72329,1],[72349,72368,19],[72369,72440,1],[72704,72712,1],[72714,72750,1],[72768,72818,50],[72819,72847,1],[72960,72966,1],[72968,72969,1],[72971,73008,1],[73030,73056,26],[73057,73061,1],[73063,73064,1],[73066,73097,1],[73112,73440,328],[73441,73458,1],[73474,73476,2],[73477,73488,1],[73490,73523,1],[73648,73728,80],[73729,74649,1],[74880,75075,1],[77712,77808,1],[77824,78895,1],[78913,78918,1],[82944,83526,1],[92160,92728,1],[92736,92766,1],[92784,92862,1],[92880,92909,1],[92928,92975,1],[92992,92995,1],[93027,93047,1],[93053,93071,1],[93760,93823,1],[93952,94026,1],[94032,94099,67],[94100,94111,1],[94176,94177,1],[94179,94208,29],[94209,100343,1],[100352,101589,1],[101632,101640,1],[110576,110579,1],[110581,110587,1],[110589,110590,1],[110592,110882,1],[110898,110928,30],[110929,110930,1],[110933,110948,15],[110949,110951,1],[110960,111355,1],[113664,113770,1],[113776,113788,1],[113792,113800,1],[113808,113817,1],[119808,119892,1],[119894,119964,1],[119966,119967,1],[119970,119973,3],[119974,119977,3],[119978,119980,1],[119982,119993,1],[119995,119997,2],[119998,120003,1],[120005,120069,1],[120071,120074,1],[120077,120084,1],[120086,120092,1],[120094,120121,1],[120123,120126,1],[120128,120132,1],[120134,120138,4],[120139,120144,1],[120146,120485,1],[120488,120512,1],[120514,120538,1],[120540,120570,1],[120572,120596,1],[120598,120628,1],[120630,120654,1],[120656,120686,1],[120688,120712,1],[120714,120744,1],[120746,120770,1],[120772,120779,1],[122624,122654,1],[122661,122666,1],[122928,122989,1],[123136,123180,1],[123191,123197,1],[123214,123536,322],[123537,123565,1],[123584,123627,1],[124112,124139,1],[124896,124902,1],[124904,124907,1],[124909,124910,1],[124912,124926,1],[124928,125124,1],[125184,125251,1],[125259,126464,1205],[126465,126467,1],[126469,126495,1],[126497,126498,1],[126500,126503,3],[126505,126514,1],[126516,126519,1],[126521,126523,2],[126530,126535,5],[126537,126541,2],[126542,126543,1],[126545,126546,1],[126548,126551,3],[126553,126561,2],[126562,126564,2],[126567,126570,1],[126572,126578,1],[126580,126583,1],[126585,126588,1],[126590,126592,2],[126593,126601,1],[126603,126619,1],[126625,126627,1],[126629,126633,1],[126635,126651,1],[131072,173791,1],[173824,177977,1],[177984,178205,1],[178208,183969,1],[183984,191456,1],[191472,192093,1],[194560,195101,1],[196608,201546,1],[201552,205743,1]]),_(p,"foldL",[[837,837,1]]),_(p,"Ll",[[97,122,1],[181,223,42],[224,246,1],[248,255,1],[257,311,2],[312,328,2],[329,375,2],[378,382,2],[383,384,1],[387,389,2],[392,396,4],[397,402,5],[405,409,4],[410,411,1],[414,417,3],[419,421,2],[424,426,2],[427,429,2],[432,436,4],[438,441,3],[442,445,3],[446,447,1],[454,460,3],[462,476,2],[477,495,2],[496,499,3],[501,505,4],[507,563,2],[564,569,1],[572,575,3],[576,578,2],[583,591,2],[592,659,1],[661,687,1],[881,883,2],[887,891,4],[892,893,1],[912,940,28],[941,974,1],[976,977,1],[981,983,1],[985,1007,2],[1008,1011,1],[1013,1019,3],[1020,1072,52],[1073,1119,1],[1121,1153,2],[1163,1215,2],[1218,1230,2],[1231,1327,2],[1376,1416,1],[4304,4346,1],[4349,4351,1],[5112,5117,1],[7296,7304,1],[7424,7467,1],[7531,7543,1],[7545,7578,1],[7681,7829,2],[7830,7837,1],[7839,7935,2],[7936,7943,1],[7952,7957,1],[7968,7975,1],[7984,7991,1],[8e3,8005,1],[8016,8023,1],[8032,8039,1],[8048,8061,1],[8064,8071,1],[8080,8087,1],[8096,8103,1],[8112,8116,1],[8118,8119,1],[8126,8130,4],[8131,8132,1],[8134,8135,1],[8144,8147,1],[8150,8151,1],[8160,8167,1],[8178,8180,1],[8182,8183,1],[8458,8462,4],[8463,8467,4],[8495,8505,5],[8508,8509,1],[8518,8521,1],[8526,8580,54],[11312,11359,1],[11361,11365,4],[11366,11372,2],[11377,11379,2],[11380,11382,2],[11383,11387,1],[11393,11491,2],[11492,11500,8],[11502,11507,5],[11520,11557,1],[11559,11565,6],[42561,42605,2],[42625,42651,2],[42787,42799,2],[42800,42801,1],[42803,42865,2],[42866,42872,1],[42874,42876,2],[42879,42887,2],[42892,42894,2],[42897,42899,2],[42900,42901,1],[42903,42921,2],[42927,42933,6],[42935,42947,2],[42952,42954,2],[42961,42969,2],[42998,43002,4],[43824,43866,1],[43872,43880,1],[43888,43967,1],[64256,64262,1],[64275,64279,1],[65345,65370,1],[66600,66639,1],[66776,66811,1],[66967,66977,1],[66979,66993,1],[66995,67001,1],[67003,67004,1],[68800,68850,1],[71872,71903,1],[93792,93823,1],[119834,119859,1],[119886,119892,1],[119894,119911,1],[119938,119963,1],[119990,119993,1],[119995,119997,2],[119998,120003,1],[120005,120015,1],[120042,120067,1],[120094,120119,1],[120146,120171,1],[120198,120223,1],[120250,120275,1],[120302,120327,1],[120354,120379,1],[120406,120431,1],[120458,120485,1],[120514,120538,1],[120540,120545,1],[120572,120596,1],[120598,120603,1],[120630,120654,1],[120656,120661,1],[120688,120712,1],[120714,120719,1],[120746,120770,1],[120772,120777,1],[120779,122624,1845],[122625,122633,1],[122635,122654,1],[122661,122666,1],[125218,125251,1]]),_(p,"foldLl",[[65,90,1],[192,214,1],[216,222,1],[256,302,2],[306,310,2],[313,327,2],[330,376,2],[377,381,2],[385,386,1],[388,390,2],[391,393,2],[394,395,1],[398,401,1],[403,404,1],[406,408,1],[412,413,1],[415,416,1],[418,422,2],[423,425,2],[428,430,2],[431,433,2],[434,435,1],[437,439,2],[440,444,4],[452,453,1],[455,456,1],[458,459,1],[461,475,2],[478,494,2],[497,498,1],[500,502,2],[503,504,1],[506,562,2],[570,571,1],[573,574,1],[577,579,2],[580,582,1],[584,590,2],[837,880,43],[882,886,4],[895,902,7],[904,906,1],[908,910,2],[911,913,2],[914,929,1],[931,939,1],[975,984,9],[986,1006,2],[1012,1015,3],[1017,1018,1],[1021,1071,1],[1120,1152,2],[1162,1216,2],[1217,1229,2],[1232,1326,2],[1329,1366,1],[4256,4293,1],[4295,4301,6],[5024,5109,1],[7312,7354,1],[7357,7359,1],[7680,7828,2],[7838,7934,2],[7944,7951,1],[7960,7965,1],[7976,7983,1],[7992,7999,1],[8008,8013,1],[8025,8031,2],[8040,8047,1],[8072,8079,1],[8088,8095,1],[8104,8111,1],[8120,8124,1],[8136,8140,1],[8152,8155,1],[8168,8172,1],[8184,8188,1],[8486,8490,4],[8491,8498,7],[8579,11264,2685],[11265,11311,1],[11360,11362,2],[11363,11364,1],[11367,11373,2],[11374,11376,1],[11378,11381,3],[11390,11392,1],[11394,11490,2],[11499,11501,2],[11506,42560,31054],[42562,42604,2],[42624,42650,2],[42786,42798,2],[42802,42862,2],[42873,42877,2],[42878,42886,2],[42891,42893,2],[42896,42898,2],[42902,42922,2],[42923,42926,1],[42928,42932,1],[42934,42948,2],[42949,42951,1],[42953,42960,7],[42966,42968,2],[42997,65313,22316],[65314,65338,1],[66560,66599,1],[66736,66771,1],[66928,66938,1],[66940,66954,1],[66956,66962,1],[66964,66965,1],[68736,68786,1],[71840,71871,1],[93760,93791,1],[125184,125217,1]]),_(p,"Lm",[[688,705,1],[710,721,1],[736,740,1],[748,750,2],[884,890,6],[1369,1600,231],[1765,1766,1],[2036,2037,1],[2042,2074,32],[2084,2088,4],[2249,2417,168],[3654,3782,128],[4348,6103,1755],[6211,6823,612],[7288,7293,1],[7468,7530,1],[7544,7579,35],[7580,7615,1],[8305,8319,14],[8336,8348,1],[11388,11389,1],[11631,11823,192],[12293,12337,44],[12338,12341,1],[12347,12445,98],[12446,12540,94],[12541,12542,1],[40981,42232,1251],[42233,42237,1],[42508,42623,115],[42652,42653,1],[42775,42783,1],[42864,42888,24],[42994,42996,1],[43e3,43001,1],[43471,43494,23],[43632,43741,109],[43763,43764,1],[43868,43871,1],[43881,65392,21511],[65438,65439,1],[67456,67461,1],[67463,67504,1],[67506,67514,1],[92992,92995,1],[94099,94111,1],[94176,94177,1],[94179,110576,16397],[110577,110579,1],[110581,110587,1],[110589,110590,1],[122928,122989,1],[123191,123197,1],[124139,125259,1120]]),_(p,"Lo",[[170,186,16],[443,448,5],[449,451,1],[660,1488,828],[1489,1514,1],[1519,1522,1],[1568,1599,1],[1601,1610,1],[1646,1647,1],[1649,1747,1],[1749,1774,25],[1775,1786,11],[1787,1788,1],[1791,1808,17],[1810,1839,1],[1869,1957,1],[1969,1994,25],[1995,2026,1],[2048,2069,1],[2112,2136,1],[2144,2154,1],[2160,2183,1],[2185,2190,1],[2208,2248,1],[2308,2361,1],[2365,2384,19],[2392,2401,1],[2418,2432,1],[2437,2444,1],[2447,2448,1],[2451,2472,1],[2474,2480,1],[2482,2486,4],[2487,2489,1],[2493,2510,17],[2524,2525,1],[2527,2529,1],[2544,2545,1],[2556,2565,9],[2566,2570,1],[2575,2576,1],[2579,2600,1],[2602,2608,1],[2610,2611,1],[2613,2614,1],[2616,2617,1],[2649,2652,1],[2654,2674,20],[2675,2676,1],[2693,2701,1],[2703,2705,1],[2707,2728,1],[2730,2736,1],[2738,2739,1],[2741,2745,1],[2749,2768,19],[2784,2785,1],[2809,2821,12],[2822,2828,1],[2831,2832,1],[2835,2856,1],[2858,2864,1],[2866,2867,1],[2869,2873,1],[2877,2908,31],[2909,2911,2],[2912,2913,1],[2929,2947,18],[2949,2954,1],[2958,2960,1],[2962,2965,1],[2969,2970,1],[2972,2974,2],[2975,2979,4],[2980,2984,4],[2985,2986,1],[2990,3001,1],[3024,3077,53],[3078,3084,1],[3086,3088,1],[3090,3112,1],[3114,3129,1],[3133,3160,27],[3161,3162,1],[3165,3168,3],[3169,3200,31],[3205,3212,1],[3214,3216,1],[3218,3240,1],[3242,3251,1],[3253,3257,1],[3261,3293,32],[3294,3296,2],[3297,3313,16],[3314,3332,18],[3333,3340,1],[3342,3344,1],[3346,3386,1],[3389,3406,17],[3412,3414,1],[3423,3425,1],[3450,3455,1],[3461,3478,1],[3482,3505,1],[3507,3515,1],[3517,3520,3],[3521,3526,1],[3585,3632,1],[3634,3635,1],[3648,3653,1],[3713,3714,1],[3716,3718,2],[3719,3722,1],[3724,3747,1],[3749,3751,2],[3752,3760,1],[3762,3763,1],[3773,3776,3],[3777,3780,1],[3804,3807,1],[3840,3904,64],[3905,3911,1],[3913,3948,1],[3976,3980,1],[4096,4138,1],[4159,4176,17],[4177,4181,1],[4186,4189,1],[4193,4197,4],[4198,4206,8],[4207,4208,1],[4213,4225,1],[4238,4352,114],[4353,4680,1],[4682,4685,1],[4688,4694,1],[4696,4698,2],[4699,4701,1],[4704,4744,1],[4746,4749,1],[4752,4784,1],[4786,4789,1],[4792,4798,1],[4800,4802,2],[4803,4805,1],[4808,4822,1],[4824,4880,1],[4882,4885,1],[4888,4954,1],[4992,5007,1],[5121,5740,1],[5743,5759,1],[5761,5786,1],[5792,5866,1],[5873,5880,1],[5888,5905,1],[5919,5937,1],[5952,5969,1],[5984,5996,1],[5998,6e3,1],[6016,6067,1],[6108,6176,68],[6177,6210,1],[6212,6264,1],[6272,6276,1],[6279,6312,1],[6314,6320,6],[6321,6389,1],[6400,6430,1],[6480,6509,1],[6512,6516,1],[6528,6571,1],[6576,6601,1],[6656,6678,1],[6688,6740,1],[6917,6963,1],[6981,6988,1],[7043,7072,1],[7086,7087,1],[7098,7141,1],[7168,7203,1],[7245,7247,1],[7258,7287,1],[7401,7404,1],[7406,7411,1],[7413,7414,1],[7418,8501,1083],[8502,8504,1],[11568,11623,1],[11648,11670,1],[11680,11686,1],[11688,11694,1],[11696,11702,1],[11704,11710,1],[11712,11718,1],[11720,11726,1],[11728,11734,1],[11736,11742,1],[12294,12348,54],[12353,12438,1],[12447,12449,2],[12450,12538,1],[12543,12549,6],[12550,12591,1],[12593,12686,1],[12704,12735,1],[12784,12799,1],[13312,19903,1],[19968,40980,1],[40982,42124,1],[42192,42231,1],[42240,42507,1],[42512,42527,1],[42538,42539,1],[42606,42656,50],[42657,42725,1],[42895,42999,104],[43003,43009,1],[43011,43013,1],[43015,43018,1],[43020,43042,1],[43072,43123,1],[43138,43187,1],[43250,43255,1],[43259,43261,2],[43262,43274,12],[43275,43301,1],[43312,43334,1],[43360,43388,1],[43396,43442,1],[43488,43492,1],[43495,43503,1],[43514,43518,1],[43520,43560,1],[43584,43586,1],[43588,43595,1],[43616,43631,1],[43633,43638,1],[43642,43646,4],[43647,43695,1],[43697,43701,4],[43702,43705,3],[43706,43709,1],[43712,43714,2],[43739,43740,1],[43744,43754,1],[43762,43777,15],[43778,43782,1],[43785,43790,1],[43793,43798,1],[43808,43814,1],[43816,43822,1],[43968,44002,1],[44032,55203,1],[55216,55238,1],[55243,55291,1],[63744,64109,1],[64112,64217,1],[64285,64287,2],[64288,64296,1],[64298,64310,1],[64312,64316,1],[64318,64320,2],[64321,64323,2],[64324,64326,2],[64327,64433,1],[64467,64829,1],[64848,64911,1],[64914,64967,1],[65008,65019,1],[65136,65140,1],[65142,65276,1],[65382,65391,1],[65393,65437,1],[65440,65470,1],[65474,65479,1],[65482,65487,1],[65490,65495,1],[65498,65500,1],[65536,65547,1],[65549,65574,1],[65576,65594,1],[65596,65597,1],[65599,65613,1],[65616,65629,1],[65664,65786,1],[66176,66204,1],[66208,66256,1],[66304,66335,1],[66349,66368,1],[66370,66377,1],[66384,66421,1],[66432,66461,1],[66464,66499,1],[66504,66511,1],[66640,66717,1],[66816,66855,1],[66864,66915,1],[67072,67382,1],[67392,67413,1],[67424,67431,1],[67584,67589,1],[67592,67594,2],[67595,67637,1],[67639,67640,1],[67644,67647,3],[67648,67669,1],[67680,67702,1],[67712,67742,1],[67808,67826,1],[67828,67829,1],[67840,67861,1],[67872,67897,1],[67968,68023,1],[68030,68031,1],[68096,68112,16],[68113,68115,1],[68117,68119,1],[68121,68149,1],[68192,68220,1],[68224,68252,1],[68288,68295,1],[68297,68324,1],[68352,68405,1],[68416,68437,1],[68448,68466,1],[68480,68497,1],[68608,68680,1],[68864,68899,1],[69248,69289,1],[69296,69297,1],[69376,69404,1],[69415,69424,9],[69425,69445,1],[69488,69505,1],[69552,69572,1],[69600,69622,1],[69635,69687,1],[69745,69746,1],[69749,69763,14],[69764,69807,1],[69840,69864,1],[69891,69926,1],[69956,69959,3],[69968,70002,1],[70006,70019,13],[70020,70066,1],[70081,70084,1],[70106,70108,2],[70144,70161,1],[70163,70187,1],[70207,70208,1],[70272,70278,1],[70280,70282,2],[70283,70285,1],[70287,70301,1],[70303,70312,1],[70320,70366,1],[70405,70412,1],[70415,70416,1],[70419,70440,1],[70442,70448,1],[70450,70451,1],[70453,70457,1],[70461,70480,19],[70493,70497,1],[70656,70708,1],[70727,70730,1],[70751,70753,1],[70784,70831,1],[70852,70853,1],[70855,71040,185],[71041,71086,1],[71128,71131,1],[71168,71215,1],[71236,71296,60],[71297,71338,1],[71352,71424,72],[71425,71450,1],[71488,71494,1],[71680,71723,1],[71935,71942,1],[71945,71948,3],[71949,71955,1],[71957,71958,1],[71960,71983,1],[71999,72001,2],[72096,72103,1],[72106,72144,1],[72161,72163,2],[72192,72203,11],[72204,72242,1],[72250,72272,22],[72284,72329,1],[72349,72368,19],[72369,72440,1],[72704,72712,1],[72714,72750,1],[72768,72818,50],[72819,72847,1],[72960,72966,1],[72968,72969,1],[72971,73008,1],[73030,73056,26],[73057,73061,1],[73063,73064,1],[73066,73097,1],[73112,73440,328],[73441,73458,1],[73474,73476,2],[73477,73488,1],[73490,73523,1],[73648,73728,80],[73729,74649,1],[74880,75075,1],[77712,77808,1],[77824,78895,1],[78913,78918,1],[82944,83526,1],[92160,92728,1],[92736,92766,1],[92784,92862,1],[92880,92909,1],[92928,92975,1],[93027,93047,1],[93053,93071,1],[93952,94026,1],[94032,94208,176],[94209,100343,1],[100352,101589,1],[101632,101640,1],[110592,110882,1],[110898,110928,30],[110929,110930,1],[110933,110948,15],[110949,110951,1],[110960,111355,1],[113664,113770,1],[113776,113788,1],[113792,113800,1],[113808,113817,1],[122634,123136,502],[123137,123180,1],[123214,123536,322],[123537,123565,1],[123584,123627,1],[124112,124138,1],[124896,124902,1],[124904,124907,1],[124909,124910,1],[124912,124926,1],[124928,125124,1],[126464,126467,1],[126469,126495,1],[126497,126498,1],[126500,126503,3],[126505,126514,1],[126516,126519,1],[126521,126523,2],[126530,126535,5],[126537,126541,2],[126542,126543,1],[126545,126546,1],[126548,126551,3],[126553,126561,2],[126562,126564,2],[126567,126570,1],[126572,126578,1],[126580,126583,1],[126585,126588,1],[126590,126592,2],[126593,126601,1],[126603,126619,1],[126625,126627,1],[126629,126633,1],[126635,126651,1],[131072,173791,1],[173824,177977,1],[177984,178205,1],[178208,183969,1],[183984,191456,1],[191472,192093,1],[194560,195101,1],[196608,201546,1],[201552,205743,1]]),_(p,"Lt",[[453,459,3],[498,8072,7574],[8073,8079,1],[8088,8095,1],[8104,8111,1],[8124,8140,16],[8188,8188,1]]),_(p,"foldLt",[[452,454,2],[455,457,2],[458,460,2],[497,499,2],[8064,8071,1],[8080,8087,1],[8096,8103,1],[8115,8131,16],[8179,8179,1]]),_(p,"Lu",[[65,90,1],[192,214,1],[216,222,1],[256,310,2],[313,327,2],[330,376,2],[377,381,2],[385,386,1],[388,390,2],[391,393,2],[394,395,1],[398,401,1],[403,404,1],[406,408,1],[412,413,1],[415,416,1],[418,422,2],[423,425,2],[428,430,2],[431,433,2],[434,435,1],[437,439,2],[440,444,4],[452,461,3],[463,475,2],[478,494,2],[497,500,3],[502,504,1],[506,562,2],[570,571,1],[573,574,1],[577,579,2],[580,582,1],[584,590,2],[880,882,2],[886,895,9],[902,904,2],[905,906,1],[908,910,2],[911,913,2],[914,929,1],[931,939,1],[975,978,3],[979,980,1],[984,1006,2],[1012,1015,3],[1017,1018,1],[1021,1071,1],[1120,1152,2],[1162,1216,2],[1217,1229,2],[1232,1326,2],[1329,1366,1],[4256,4293,1],[4295,4301,6],[5024,5109,1],[7312,7354,1],[7357,7359,1],[7680,7828,2],[7838,7934,2],[7944,7951,1],[7960,7965,1],[7976,7983,1],[7992,7999,1],[8008,8013,1],[8025,8031,2],[8040,8047,1],[8120,8123,1],[8136,8139,1],[8152,8155,1],[8168,8172,1],[8184,8187,1],[8450,8455,5],[8459,8461,1],[8464,8466,1],[8469,8473,4],[8474,8477,1],[8484,8490,2],[8491,8493,1],[8496,8499,1],[8510,8511,1],[8517,8579,62],[11264,11311,1],[11360,11362,2],[11363,11364,1],[11367,11373,2],[11374,11376,1],[11378,11381,3],[11390,11392,1],[11394,11490,2],[11499,11501,2],[11506,42560,31054],[42562,42604,2],[42624,42650,2],[42786,42798,2],[42802,42862,2],[42873,42877,2],[42878,42886,2],[42891,42893,2],[42896,42898,2],[42902,42922,2],[42923,42926,1],[42928,42932,1],[42934,42948,2],[42949,42951,1],[42953,42960,7],[42966,42968,2],[42997,65313,22316],[65314,65338,1],[66560,66599,1],[66736,66771,1],[66928,66938,1],[66940,66954,1],[66956,66962,1],[66964,66965,1],[68736,68786,1],[71840,71871,1],[93760,93791,1],[119808,119833,1],[119860,119885,1],[119912,119937,1],[119964,119966,2],[119967,119973,3],[119974,119977,3],[119978,119980,1],[119982,119989,1],[120016,120041,1],[120068,120069,1],[120071,120074,1],[120077,120084,1],[120086,120092,1],[120120,120121,1],[120123,120126,1],[120128,120132,1],[120134,120138,4],[120139,120144,1],[120172,120197,1],[120224,120249,1],[120276,120301,1],[120328,120353,1],[120380,120405,1],[120432,120457,1],[120488,120512,1],[120546,120570,1],[120604,120628,1],[120662,120686,1],[120720,120744,1],[120778,125184,4406],[125185,125217,1]]),_(p,"Upper",p.Lu),_(p,"foldLu",[[97,122,1],[181,223,42],[224,246,1],[248,255,1],[257,303,2],[307,311,2],[314,328,2],[331,375,2],[378,382,2],[383,384,1],[387,389,2],[392,396,4],[402,405,3],[409,410,1],[414,417,3],[419,421,2],[424,429,5],[432,436,4],[438,441,3],[445,447,2],[453,454,1],[456,457,1],[459,460,1],[462,476,2],[477,495,2],[498,499,1],[501,505,4],[507,543,2],[547,563,2],[572,575,3],[576,578,2],[583,591,2],[592,596,1],[598,599,1],[601,603,2],[604,608,4],[609,613,2],[614,616,2],[617,620,1],[623,625,2],[626,629,3],[637,640,3],[642,643,1],[647,652,1],[658,669,11],[670,837,167],[881,883,2],[887,891,4],[892,893,1],[940,943,1],[945,974,1],[976,977,1],[981,983,1],[985,1007,2],[1008,1011,1],[1013,1019,3],[1072,1119,1],[1121,1153,2],[1163,1215,2],[1218,1230,2],[1231,1327,2],[1377,1414,1],[4304,4346,1],[4349,4351,1],[5112,5117,1],[7296,7304,1],[7545,7549,4],[7566,7681,115],[7683,7829,2],[7835,7841,6],[7843,7935,2],[7936,7943,1],[7952,7957,1],[7968,7975,1],[7984,7991,1],[8e3,8005,1],[8017,8023,2],[8032,8039,1],[8048,8061,1],[8112,8113,1],[8126,8144,18],[8145,8160,15],[8161,8165,4],[8526,8580,54],[11312,11359,1],[11361,11365,4],[11366,11372,2],[11379,11382,3],[11393,11491,2],[11500,11502,2],[11507,11520,13],[11521,11557,1],[11559,11565,6],[42561,42605,2],[42625,42651,2],[42787,42799,2],[42803,42863,2],[42874,42876,2],[42879,42887,2],[42892,42897,5],[42899,42900,1],[42903,42921,2],[42933,42947,2],[42952,42954,2],[42961,42967,6],[42969,42998,29],[43859,43888,29],[43889,43967,1],[65345,65370,1],[66600,66639,1],[66776,66811,1],[66967,66977,1],[66979,66993,1],[66995,67001,1],[67003,67004,1],[68800,68850,1],[71872,71903,1],[93792,93823,1],[125218,125251,1]]),_(p,"M",[[768,879,1],[1155,1161,1],[1425,1469,1],[1471,1473,2],[1474,1476,2],[1477,1479,2],[1552,1562,1],[1611,1631,1],[1648,1750,102],[1751,1756,1],[1759,1764,1],[1767,1768,1],[1770,1773,1],[1809,1840,31],[1841,1866,1],[1958,1968,1],[2027,2035,1],[2045,2070,25],[2071,2073,1],[2075,2083,1],[2085,2087,1],[2089,2093,1],[2137,2139,1],[2200,2207,1],[2250,2273,1],[2275,2307,1],[2362,2364,1],[2366,2383,1],[2385,2391,1],[2402,2403,1],[2433,2435,1],[2492,2494,2],[2495,2500,1],[2503,2504,1],[2507,2509,1],[2519,2530,11],[2531,2558,27],[2561,2563,1],[2620,2622,2],[2623,2626,1],[2631,2632,1],[2635,2637,1],[2641,2672,31],[2673,2677,4],[2689,2691,1],[2748,2750,2],[2751,2757,1],[2759,2761,1],[2763,2765,1],[2786,2787,1],[2810,2815,1],[2817,2819,1],[2876,2878,2],[2879,2884,1],[2887,2888,1],[2891,2893,1],[2901,2903,1],[2914,2915,1],[2946,3006,60],[3007,3010,1],[3014,3016,1],[3018,3021,1],[3031,3072,41],[3073,3076,1],[3132,3134,2],[3135,3140,1],[3142,3144,1],[3146,3149,1],[3157,3158,1],[3170,3171,1],[3201,3203,1],[3260,3262,2],[3263,3268,1],[3270,3272,1],[3274,3277,1],[3285,3286,1],[3298,3299,1],[3315,3328,13],[3329,3331,1],[3387,3388,1],[3390,3396,1],[3398,3400,1],[3402,3405,1],[3415,3426,11],[3427,3457,30],[3458,3459,1],[3530,3535,5],[3536,3540,1],[3542,3544,2],[3545,3551,1],[3570,3571,1],[3633,3636,3],[3637,3642,1],[3655,3662,1],[3761,3764,3],[3765,3772,1],[3784,3790,1],[3864,3865,1],[3893,3897,2],[3902,3903,1],[3953,3972,1],[3974,3975,1],[3981,3991,1],[3993,4028,1],[4038,4139,101],[4140,4158,1],[4182,4185,1],[4190,4192,1],[4194,4196,1],[4199,4205,1],[4209,4212,1],[4226,4237,1],[4239,4250,11],[4251,4253,1],[4957,4959,1],[5906,5909,1],[5938,5940,1],[5970,5971,1],[6002,6003,1],[6068,6099,1],[6109,6155,46],[6156,6157,1],[6159,6277,118],[6278,6313,35],[6432,6443,1],[6448,6459,1],[6679,6683,1],[6741,6750,1],[6752,6780,1],[6783,6832,49],[6833,6862,1],[6912,6916,1],[6964,6980,1],[7019,7027,1],[7040,7042,1],[7073,7085,1],[7142,7155,1],[7204,7223,1],[7376,7378,1],[7380,7400,1],[7405,7412,7],[7415,7417,1],[7616,7679,1],[8400,8432,1],[11503,11505,1],[11647,11744,97],[11745,11775,1],[12330,12335,1],[12441,12442,1],[42607,42610,1],[42612,42621,1],[42654,42655,1],[42736,42737,1],[43010,43014,4],[43019,43043,24],[43044,43047,1],[43052,43136,84],[43137,43188,51],[43189,43205,1],[43232,43249,1],[43263,43302,39],[43303,43309,1],[43335,43347,1],[43392,43395,1],[43443,43456,1],[43493,43561,68],[43562,43574,1],[43587,43596,9],[43597,43643,46],[43644,43645,1],[43696,43698,2],[43699,43700,1],[43703,43704,1],[43710,43711,1],[43713,43755,42],[43756,43759,1],[43765,43766,1],[44003,44010,1],[44012,44013,1],[64286,65024,738],[65025,65039,1],[65056,65071,1],[66045,66272,227],[66422,66426,1],[68097,68099,1],[68101,68102,1],[68108,68111,1],[68152,68154,1],[68159,68325,166],[68326,68900,574],[68901,68903,1],[69291,69292,1],[69373,69375,1],[69446,69456,1],[69506,69509,1],[69632,69634,1],[69688,69702,1],[69744,69747,3],[69748,69759,11],[69760,69762,1],[69808,69818,1],[69826,69888,62],[69889,69890,1],[69927,69940,1],[69957,69958,1],[70003,70016,13],[70017,70018,1],[70067,70080,1],[70089,70092,1],[70094,70095,1],[70188,70199,1],[70206,70209,3],[70367,70378,1],[70400,70403,1],[70459,70460,1],[70462,70468,1],[70471,70472,1],[70475,70477,1],[70487,70498,11],[70499,70502,3],[70503,70508,1],[70512,70516,1],[70709,70726,1],[70750,70832,82],[70833,70851,1],[71087,71093,1],[71096,71104,1],[71132,71133,1],[71216,71232,1],[71339,71351,1],[71453,71467,1],[71724,71738,1],[71984,71989,1],[71991,71992,1],[71995,71998,1],[72e3,72002,2],[72003,72145,142],[72146,72151,1],[72154,72160,1],[72164,72193,29],[72194,72202,1],[72243,72249,1],[72251,72254,1],[72263,72273,10],[72274,72283,1],[72330,72345,1],[72751,72758,1],[72760,72767,1],[72850,72871,1],[72873,72886,1],[73009,73014,1],[73018,73020,2],[73021,73023,2],[73024,73029,1],[73031,73098,67],[73099,73102,1],[73104,73105,1],[73107,73111,1],[73459,73462,1],[73472,73473,1],[73475,73524,49],[73525,73530,1],[73534,73538,1],[78912,78919,7],[78920,78933,1],[92912,92916,1],[92976,92982,1],[94031,94033,2],[94034,94087,1],[94095,94098,1],[94180,94192,12],[94193,113821,19628],[113822,118528,4706],[118529,118573,1],[118576,118598,1],[119141,119145,1],[119149,119154,1],[119163,119170,1],[119173,119179,1],[119210,119213,1],[119362,119364,1],[121344,121398,1],[121403,121452,1],[121461,121476,15],[121499,121503,1],[121505,121519,1],[122880,122886,1],[122888,122904,1],[122907,122913,1],[122915,122916,1],[122918,122922,1],[123023,123184,161],[123185,123190,1],[123566,123628,62],[123629,123631,1],[124140,124143,1],[125136,125142,1],[125252,125258,1],[917760,917999,1]]),_(p,"foldM",[[921,953,32],[8126,8126,1]]),_(p,"Mc",[[2307,2363,56],[2366,2368,1],[2377,2380,1],[2382,2383,1],[2434,2435,1],[2494,2496,1],[2503,2504,1],[2507,2508,1],[2519,2563,44],[2622,2624,1],[2691,2750,59],[2751,2752,1],[2761,2763,2],[2764,2818,54],[2819,2878,59],[2880,2887,7],[2888,2891,3],[2892,2903,11],[3006,3007,1],[3009,3010,1],[3014,3016,1],[3018,3020,1],[3031,3073,42],[3074,3075,1],[3137,3140,1],[3202,3203,1],[3262,3264,2],[3265,3268,1],[3271,3272,1],[3274,3275,1],[3285,3286,1],[3315,3330,15],[3331,3390,59],[3391,3392,1],[3398,3400,1],[3402,3404,1],[3415,3458,43],[3459,3535,76],[3536,3537,1],[3544,3551,1],[3570,3571,1],[3902,3903,1],[3967,4139,172],[4140,4145,5],[4152,4155,3],[4156,4182,26],[4183,4194,11],[4195,4196,1],[4199,4205,1],[4227,4228,1],[4231,4236,1],[4239,4250,11],[4251,4252,1],[5909,5940,31],[6070,6078,8],[6079,6085,1],[6087,6088,1],[6435,6438,1],[6441,6443,1],[6448,6449,1],[6451,6456,1],[6681,6682,1],[6741,6743,2],[6753,6755,2],[6756,6765,9],[6766,6770,1],[6916,6965,49],[6971,6973,2],[6974,6977,1],[6979,6980,1],[7042,7073,31],[7078,7079,1],[7082,7143,61],[7146,7148,1],[7150,7154,4],[7155,7204,49],[7205,7211,1],[7220,7221,1],[7393,7415,22],[12334,12335,1],[43043,43044,1],[43047,43136,89],[43137,43188,51],[43189,43203,1],[43346,43347,1],[43395,43444,49],[43445,43450,5],[43451,43454,3],[43455,43456,1],[43567,43568,1],[43571,43572,1],[43597,43643,46],[43645,43755,110],[43758,43759,1],[43765,44003,238],[44004,44006,2],[44007,44009,2],[44010,44012,2],[69632,69634,2],[69762,69808,46],[69809,69810,1],[69815,69816,1],[69932,69957,25],[69958,70018,60],[70067,70069,1],[70079,70080,1],[70094,70188,94],[70189,70190,1],[70194,70195,1],[70197,70368,171],[70369,70370,1],[70402,70403,1],[70462,70463,1],[70465,70468,1],[70471,70472,1],[70475,70477,1],[70487,70498,11],[70499,70709,210],[70710,70711,1],[70720,70721,1],[70725,70832,107],[70833,70834,1],[70841,70843,2],[70844,70846,1],[70849,71087,238],[71088,71089,1],[71096,71099,1],[71102,71216,114],[71217,71218,1],[71227,71228,1],[71230,71340,110],[71342,71343,1],[71350,71456,106],[71457,71462,5],[71724,71726,1],[71736,71984,248],[71985,71989,1],[71991,71992,1],[71997,72e3,3],[72002,72145,143],[72146,72147,1],[72156,72159,1],[72164,72249,85],[72279,72280,1],[72343,72751,408],[72766,72873,107],[72881,72884,3],[73098,73102,1],[73107,73108,1],[73110,73461,351],[73462,73475,13],[73524,73525,1],[73534,73535,1],[73537,94033,20496],[94034,94087,1],[94192,94193,1],[119141,119142,1],[119149,119154,1]]),_(p,"Me",[[1160,1161,1],[6846,8413,1567],[8414,8416,1],[8418,8420,1],[42608,42610,1]]),_(p,"Mn",[[768,879,1],[1155,1159,1],[1425,1469,1],[1471,1473,2],[1474,1476,2],[1477,1479,2],[1552,1562,1],[1611,1631,1],[1648,1750,102],[1751,1756,1],[1759,1764,1],[1767,1768,1],[1770,1773,1],[1809,1840,31],[1841,1866,1],[1958,1968,1],[2027,2035,1],[2045,2070,25],[2071,2073,1],[2075,2083,1],[2085,2087,1],[2089,2093,1],[2137,2139,1],[2200,2207,1],[2250,2273,1],[2275,2306,1],[2362,2364,2],[2369,2376,1],[2381,2385,4],[2386,2391,1],[2402,2403,1],[2433,2492,59],[2497,2500,1],[2509,2530,21],[2531,2558,27],[2561,2562,1],[2620,2625,5],[2626,2631,5],[2632,2635,3],[2636,2637,1],[2641,2672,31],[2673,2677,4],[2689,2690,1],[2748,2753,5],[2754,2757,1],[2759,2760,1],[2765,2786,21],[2787,2810,23],[2811,2815,1],[2817,2876,59],[2879,2881,2],[2882,2884,1],[2893,2901,8],[2902,2914,12],[2915,2946,31],[3008,3021,13],[3072,3076,4],[3132,3134,2],[3135,3136,1],[3142,3144,1],[3146,3149,1],[3157,3158,1],[3170,3171,1],[3201,3260,59],[3263,3270,7],[3276,3277,1],[3298,3299,1],[3328,3329,1],[3387,3388,1],[3393,3396,1],[3405,3426,21],[3427,3457,30],[3530,3538,8],[3539,3540,1],[3542,3633,91],[3636,3642,1],[3655,3662,1],[3761,3764,3],[3765,3772,1],[3784,3790,1],[3864,3865,1],[3893,3897,2],[3953,3966,1],[3968,3972,1],[3974,3975,1],[3981,3991,1],[3993,4028,1],[4038,4141,103],[4142,4144,1],[4146,4151,1],[4153,4154,1],[4157,4158,1],[4184,4185,1],[4190,4192,1],[4209,4212,1],[4226,4229,3],[4230,4237,7],[4253,4957,704],[4958,4959,1],[5906,5908,1],[5938,5939,1],[5970,5971,1],[6002,6003,1],[6068,6069,1],[6071,6077,1],[6086,6089,3],[6090,6099,1],[6109,6155,46],[6156,6157,1],[6159,6277,118],[6278,6313,35],[6432,6434,1],[6439,6440,1],[6450,6457,7],[6458,6459,1],[6679,6680,1],[6683,6742,59],[6744,6750,1],[6752,6754,2],[6757,6764,1],[6771,6780,1],[6783,6832,49],[6833,6845,1],[6847,6862,1],[6912,6915,1],[6964,6966,2],[6967,6970,1],[6972,6978,6],[7019,7027,1],[7040,7041,1],[7074,7077,1],[7080,7081,1],[7083,7085,1],[7142,7144,2],[7145,7149,4],[7151,7153,1],[7212,7219,1],[7222,7223,1],[7376,7378,1],[7380,7392,1],[7394,7400,1],[7405,7412,7],[7416,7417,1],[7616,7679,1],[8400,8412,1],[8417,8421,4],[8422,8432,1],[11503,11505,1],[11647,11744,97],[11745,11775,1],[12330,12333,1],[12441,12442,1],[42607,42612,5],[42613,42621,1],[42654,42655,1],[42736,42737,1],[43010,43014,4],[43019,43045,26],[43046,43052,6],[43204,43205,1],[43232,43249,1],[43263,43302,39],[43303,43309,1],[43335,43345,1],[43392,43394,1],[43443,43446,3],[43447,43449,1],[43452,43453,1],[43493,43561,68],[43562,43566,1],[43569,43570,1],[43573,43574,1],[43587,43596,9],[43644,43696,52],[43698,43700,1],[43703,43704,1],[43710,43711,1],[43713,43756,43],[43757,43766,9],[44005,44008,3],[44013,64286,20273],[65024,65039,1],[65056,65071,1],[66045,66272,227],[66422,66426,1],[68097,68099,1],[68101,68102,1],[68108,68111,1],[68152,68154,1],[68159,68325,166],[68326,68900,574],[68901,68903,1],[69291,69292,1],[69373,69375,1],[69446,69456,1],[69506,69509,1],[69633,69688,55],[69689,69702,1],[69744,69747,3],[69748,69759,11],[69760,69761,1],[69811,69814,1],[69817,69818,1],[69826,69888,62],[69889,69890,1],[69927,69931,1],[69933,69940,1],[70003,70016,13],[70017,70070,53],[70071,70078,1],[70089,70092,1],[70095,70191,96],[70192,70193,1],[70196,70198,2],[70199,70206,7],[70209,70367,158],[70371,70378,1],[70400,70401,1],[70459,70460,1],[70464,70502,38],[70503,70508,1],[70512,70516,1],[70712,70719,1],[70722,70724,1],[70726,70750,24],[70835,70840,1],[70842,70847,5],[70848,70850,2],[70851,71090,239],[71091,71093,1],[71100,71101,1],[71103,71104,1],[71132,71133,1],[71219,71226,1],[71229,71231,2],[71232,71339,107],[71341,71344,3],[71345,71349,1],[71351,71453,102],[71454,71455,1],[71458,71461,1],[71463,71467,1],[71727,71735,1],[71737,71738,1],[71995,71996,1],[71998,72003,5],[72148,72151,1],[72154,72155,1],[72160,72193,33],[72194,72202,1],[72243,72248,1],[72251,72254,1],[72263,72273,10],[72274,72278,1],[72281,72283,1],[72330,72342,1],[72344,72345,1],[72752,72758,1],[72760,72765,1],[72767,72850,83],[72851,72871,1],[72874,72880,1],[72882,72883,1],[72885,72886,1],[73009,73014,1],[73018,73020,2],[73021,73023,2],[73024,73029,1],[73031,73104,73],[73105,73109,4],[73111,73459,348],[73460,73472,12],[73473,73526,53],[73527,73530,1],[73536,73538,2],[78912,78919,7],[78920,78933,1],[92912,92916,1],[92976,92982,1],[94031,94095,64],[94096,94098,1],[94180,113821,19641],[113822,118528,4706],[118529,118573,1],[118576,118598,1],[119143,119145,1],[119163,119170,1],[119173,119179,1],[119210,119213,1],[119362,119364,1],[121344,121398,1],[121403,121452,1],[121461,121476,15],[121499,121503,1],[121505,121519,1],[122880,122886,1],[122888,122904,1],[122907,122913,1],[122915,122916,1],[122918,122922,1],[123023,123184,161],[123185,123190,1],[123566,123628,62],[123629,123631,1],[124140,124143,1],[125136,125142,1],[125252,125258,1],[917760,917999,1]]),_(p,"foldMn",[[921,953,32],[8126,8126,1]]),_(p,"N",[[48,57,1],[178,179,1],[185,188,3],[189,190,1],[1632,1641,1],[1776,1785,1],[1984,1993,1],[2406,2415,1],[2534,2543,1],[2548,2553,1],[2662,2671,1],[2790,2799,1],[2918,2927,1],[2930,2935,1],[3046,3058,1],[3174,3183,1],[3192,3198,1],[3302,3311,1],[3416,3422,1],[3430,3448,1],[3558,3567,1],[3664,3673,1],[3792,3801,1],[3872,3891,1],[4160,4169,1],[4240,4249,1],[4969,4988,1],[5870,5872,1],[6112,6121,1],[6128,6137,1],[6160,6169,1],[6470,6479,1],[6608,6618,1],[6784,6793,1],[6800,6809,1],[6992,7001,1],[7088,7097,1],[7232,7241,1],[7248,7257,1],[8304,8308,4],[8309,8313,1],[8320,8329,1],[8528,8578,1],[8581,8585,1],[9312,9371,1],[9450,9471,1],[10102,10131,1],[11517,12295,778],[12321,12329,1],[12344,12346,1],[12690,12693,1],[12832,12841,1],[12872,12879,1],[12881,12895,1],[12928,12937,1],[12977,12991,1],[42528,42537,1],[42726,42735,1],[43056,43061,1],[43216,43225,1],[43264,43273,1],[43472,43481,1],[43504,43513,1],[43600,43609,1],[44016,44025,1],[65296,65305,1],[65799,65843,1],[65856,65912,1],[65930,65931,1],[66273,66299,1],[66336,66339,1],[66369,66378,9],[66513,66517,1],[66720,66729,1],[67672,67679,1],[67705,67711,1],[67751,67759,1],[67835,67839,1],[67862,67867,1],[68028,68029,1],[68032,68047,1],[68050,68095,1],[68160,68168,1],[68221,68222,1],[68253,68255,1],[68331,68335,1],[68440,68447,1],[68472,68479,1],[68521,68527,1],[68858,68863,1],[68912,68921,1],[69216,69246,1],[69405,69414,1],[69457,69460,1],[69573,69579,1],[69714,69743,1],[69872,69881,1],[69942,69951,1],[70096,70105,1],[70113,70132,1],[70384,70393,1],[70736,70745,1],[70864,70873,1],[71248,71257,1],[71360,71369,1],[71472,71483,1],[71904,71922,1],[72016,72025,1],[72784,72812,1],[73040,73049,1],[73120,73129,1],[73552,73561,1],[73664,73684,1],[74752,74862,1],[92768,92777,1],[92864,92873,1],[93008,93017,1],[93019,93025,1],[93824,93846,1],[119488,119507,1],[119520,119539,1],[119648,119672,1],[120782,120831,1],[123200,123209,1],[123632,123641,1],[124144,124153,1],[125127,125135,1],[125264,125273,1],[126065,126123,1],[126125,126127,1],[126129,126132,1],[126209,126253,1],[126255,126269,1],[127232,127244,1],[130032,130041,1]]),_(p,"Nd",[[48,57,1],[1632,1641,1],[1776,1785,1],[1984,1993,1],[2406,2415,1],[2534,2543,1],[2662,2671,1],[2790,2799,1],[2918,2927,1],[3046,3055,1],[3174,3183,1],[3302,3311,1],[3430,3439,1],[3558,3567,1],[3664,3673,1],[3792,3801,1],[3872,3881,1],[4160,4169,1],[4240,4249,1],[6112,6121,1],[6160,6169,1],[6470,6479,1],[6608,6617,1],[6784,6793,1],[6800,6809,1],[6992,7001,1],[7088,7097,1],[7232,7241,1],[7248,7257,1],[42528,42537,1],[43216,43225,1],[43264,43273,1],[43472,43481,1],[43504,43513,1],[43600,43609,1],[44016,44025,1],[65296,65305,1],[66720,66729,1],[68912,68921,1],[69734,69743,1],[69872,69881,1],[69942,69951,1],[70096,70105,1],[70384,70393,1],[70736,70745,1],[70864,70873,1],[71248,71257,1],[71360,71369,1],[71472,71481,1],[71904,71913,1],[72016,72025,1],[72784,72793,1],[73040,73049,1],[73120,73129,1],[73552,73561,1],[92768,92777,1],[92864,92873,1],[93008,93017,1],[120782,120831,1],[123200,123209,1],[123632,123641,1],[124144,124153,1],[125264,125273,1],[130032,130041,1]]),_(p,"Nl",[[5870,5872,1],[8544,8578,1],[8581,8584,1],[12295,12321,26],[12322,12329,1],[12344,12346,1],[42726,42735,1],[65856,65908,1],[66369,66378,9],[66513,66517,1],[74752,74862,1]]),_(p,"No",[[178,179,1],[185,188,3],[189,190,1],[2548,2553,1],[2930,2935,1],[3056,3058,1],[3192,3198,1],[3416,3422,1],[3440,3448,1],[3882,3891,1],[4969,4988,1],[6128,6137,1],[6618,8304,1686],[8308,8313,1],[8320,8329,1],[8528,8543,1],[8585,9312,727],[9313,9371,1],[9450,9471,1],[10102,10131,1],[11517,12690,1173],[12691,12693,1],[12832,12841,1],[12872,12879,1],[12881,12895,1],[12928,12937,1],[12977,12991,1],[43056,43061,1],[65799,65843,1],[65909,65912,1],[65930,65931,1],[66273,66299,1],[66336,66339,1],[67672,67679,1],[67705,67711,1],[67751,67759,1],[67835,67839,1],[67862,67867,1],[68028,68029,1],[68032,68047,1],[68050,68095,1],[68160,68168,1],[68221,68222,1],[68253,68255,1],[68331,68335,1],[68440,68447,1],[68472,68479,1],[68521,68527,1],[68858,68863,1],[69216,69246,1],[69405,69414,1],[69457,69460,1],[69573,69579,1],[69714,69733,1],[70113,70132,1],[71482,71483,1],[71914,71922,1],[72794,72812,1],[73664,73684,1],[93019,93025,1],[93824,93846,1],[119488,119507,1],[119520,119539,1],[119648,119672,1],[125127,125135,1],[126065,126123,1],[126125,126127,1],[126129,126132,1],[126209,126253,1],[126255,126269,1],[127232,127244,1]]),_(p,"P",[[33,35,1],[37,42,1],[44,47,1],[58,59,1],[63,64,1],[91,93,1],[95,123,28],[125,161,36],[167,171,4],[182,183,1],[187,191,4],[894,903,9],[1370,1375,1],[1417,1418,1],[1470,1472,2],[1475,1478,3],[1523,1524,1],[1545,1546,1],[1548,1549,1],[1563,1565,2],[1566,1567,1],[1642,1645,1],[1748,1792,44],[1793,1805,1],[2039,2041,1],[2096,2110,1],[2142,2404,262],[2405,2416,11],[2557,2678,121],[2800,3191,391],[3204,3572,368],[3663,3674,11],[3675,3844,169],[3845,3858,1],[3860,3898,38],[3899,3901,1],[3973,4048,75],[4049,4052,1],[4057,4058,1],[4170,4175,1],[4347,4960,613],[4961,4968,1],[5120,5742,622],[5787,5788,1],[5867,5869,1],[5941,5942,1],[6100,6102,1],[6104,6106,1],[6144,6154,1],[6468,6469,1],[6686,6687,1],[6816,6822,1],[6824,6829,1],[7002,7008,1],[7037,7038,1],[7164,7167,1],[7227,7231,1],[7294,7295,1],[7360,7367,1],[7379,8208,829],[8209,8231,1],[8240,8259,1],[8261,8273,1],[8275,8286,1],[8317,8318,1],[8333,8334,1],[8968,8971,1],[9001,9002,1],[10088,10101,1],[10181,10182,1],[10214,10223,1],[10627,10648,1],[10712,10715,1],[10748,10749,1],[11513,11516,1],[11518,11519,1],[11632,11776,144],[11777,11822,1],[11824,11855,1],[11858,11869,1],[12289,12291,1],[12296,12305,1],[12308,12319,1],[12336,12349,13],[12448,12539,91],[42238,42239,1],[42509,42511,1],[42611,42622,11],[42738,42743,1],[43124,43127,1],[43214,43215,1],[43256,43258,1],[43260,43310,50],[43311,43359,48],[43457,43469,1],[43486,43487,1],[43612,43615,1],[43742,43743,1],[43760,43761,1],[44011,64830,20819],[64831,65040,209],[65041,65049,1],[65072,65106,1],[65108,65121,1],[65123,65128,5],[65130,65131,1],[65281,65283,1],[65285,65290,1],[65292,65295,1],[65306,65307,1],[65311,65312,1],[65339,65341,1],[65343,65371,28],[65373,65375,2],[65376,65381,1],[65792,65794,1],[66463,66512,49],[66927,67671,744],[67871,67903,32],[68176,68184,1],[68223,68336,113],[68337,68342,1],[68409,68415,1],[68505,68508,1],[69293,69461,168],[69462,69465,1],[69510,69513,1],[69703,69709,1],[69819,69820,1],[69822,69825,1],[69952,69955,1],[70004,70005,1],[70085,70088,1],[70093,70107,14],[70109,70111,1],[70200,70205,1],[70313,70731,418],[70732,70735,1],[70746,70747,1],[70749,70854,105],[71105,71127,1],[71233,71235,1],[71264,71276,1],[71353,71484,131],[71485,71486,1],[71739,72004,265],[72005,72006,1],[72162,72255,93],[72256,72262,1],[72346,72348,1],[72350,72354,1],[72448,72457,1],[72769,72773,1],[72816,72817,1],[73463,73464,1],[73539,73551,1],[73727,74864,1137],[74865,74868,1],[77809,77810,1],[92782,92783,1],[92917,92983,66],[92984,92987,1],[92996,93847,851],[93848,93850,1],[94178,113823,19645],[121479,121483,1],[125278,125279,1]]),_(p,"Pc",[[95,8255,8160],[8256,8276,20],[65075,65076,1],[65101,65103,1],[65343,65343,1]]),_(p,"Pd",[[45,1418,1373],[1470,5120,3650],[6150,8208,2058],[8209,8213,1],[11799,11802,3],[11834,11835,1],[11840,11869,29],[12316,12336,20],[12448,65073,52625],[65074,65112,38],[65123,65293,170],[69293,69293,1]]),_(p,"Pe",[[41,93,52],[125,3899,3774],[3901,5788,1887],[8262,8318,56],[8334,8969,635],[8971,9002,31],[10089,10101,2],[10182,10215,33],[10217,10223,2],[10628,10648,2],[10713,10715,2],[10749,11811,1062],[11813,11817,2],[11862,11868,2],[12297,12305,2],[12309,12315,2],[12318,12319,1],[64830,65048,218],[65078,65092,2],[65096,65114,18],[65116,65118,2],[65289,65341,52],[65373,65379,3]]),_(p,"Pf",[[187,8217,8030],[8221,8250,29],[11779,11781,2],[11786,11789,3],[11805,11809,4]]),_(p,"Pi",[[171,8216,8045],[8219,8220,1],[8223,8249,26],[11778,11780,2],[11785,11788,3],[11804,11808,4]]),_(p,"Po",[[33,35,1],[37,39,1],[42,46,2],[47,58,11],[59,63,4],[64,92,28],[161,167,6],[182,183,1],[191,894,703],[903,1370,467],[1371,1375,1],[1417,1472,55],[1475,1478,3],[1523,1524,1],[1545,1546,1],[1548,1549,1],[1563,1565,2],[1566,1567,1],[1642,1645,1],[1748,1792,44],[1793,1805,1],[2039,2041,1],[2096,2110,1],[2142,2404,262],[2405,2416,11],[2557,2678,121],[2800,3191,391],[3204,3572,368],[3663,3674,11],[3675,3844,169],[3845,3858,1],[3860,3973,113],[4048,4052,1],[4057,4058,1],[4170,4175,1],[4347,4960,613],[4961,4968,1],[5742,5867,125],[5868,5869,1],[5941,5942,1],[6100,6102,1],[6104,6106,1],[6144,6149,1],[6151,6154,1],[6468,6469,1],[6686,6687,1],[6816,6822,1],[6824,6829,1],[7002,7008,1],[7037,7038,1],[7164,7167,1],[7227,7231,1],[7294,7295,1],[7360,7367,1],[7379,8214,835],[8215,8224,9],[8225,8231,1],[8240,8248,1],[8251,8254,1],[8257,8259,1],[8263,8273,1],[8275,8277,2],[8278,8286,1],[11513,11516,1],[11518,11519,1],[11632,11776,144],[11777,11782,5],[11783,11784,1],[11787,11790,3],[11791,11798,1],[11800,11801,1],[11803,11806,3],[11807,11818,11],[11819,11822,1],[11824,11833,1],[11836,11839,1],[11841,11843,2],[11844,11855,1],[11858,11860,1],[12289,12291,1],[12349,12539,190],[42238,42239,1],[42509,42511,1],[42611,42622,11],[42738,42743,1],[43124,43127,1],[43214,43215,1],[43256,43258,1],[43260,43310,50],[43311,43359,48],[43457,43469,1],[43486,43487,1],[43612,43615,1],[43742,43743,1],[43760,43761,1],[44011,65040,21029],[65041,65046,1],[65049,65072,23],[65093,65094,1],[65097,65100,1],[65104,65106,1],[65108,65111,1],[65119,65121,1],[65128,65130,2],[65131,65281,150],[65282,65283,1],[65285,65287,1],[65290,65294,2],[65295,65306,11],[65307,65311,4],[65312,65340,28],[65377,65380,3],[65381,65792,411],[65793,65794,1],[66463,66512,49],[66927,67671,744],[67871,67903,32],[68176,68184,1],[68223,68336,113],[68337,68342,1],[68409,68415,1],[68505,68508,1],[69461,69465,1],[69510,69513,1],[69703,69709,1],[69819,69820,1],[69822,69825,1],[69952,69955,1],[70004,70005,1],[70085,70088,1],[70093,70107,14],[70109,70111,1],[70200,70205,1],[70313,70731,418],[70732,70735,1],[70746,70747,1],[70749,70854,105],[71105,71127,1],[71233,71235,1],[71264,71276,1],[71353,71484,131],[71485,71486,1],[71739,72004,265],[72005,72006,1],[72162,72255,93],[72256,72262,1],[72346,72348,1],[72350,72354,1],[72448,72457,1],[72769,72773,1],[72816,72817,1],[73463,73464,1],[73539,73551,1],[73727,74864,1137],[74865,74868,1],[77809,77810,1],[92782,92783,1],[92917,92983,66],[92984,92987,1],[92996,93847,851],[93848,93850,1],[94178,113823,19645],[121479,121483,1],[125278,125279,1]]),_(p,"Ps",[[40,91,51],[123,3898,3775],[3900,5787,1887],[8218,8222,4],[8261,8317,56],[8333,8968,635],[8970,9001,31],[10088,10100,2],[10181,10214,33],[10216,10222,2],[10627,10647,2],[10712,10714,2],[10748,11810,1062],[11812,11816,2],[11842,11861,19],[11863,11867,2],[12296,12304,2],[12308,12314,2],[12317,64831,52514],[65047,65077,30],[65079,65091,2],[65095,65113,18],[65115,65117,2],[65288,65339,51],[65371,65375,4],[65378,65378,1]]),_(p,"S",[[36,43,7],[60,62,1],[94,96,2],[124,126,2],[162,166,1],[168,169,1],[172,174,2],[175,177,1],[180,184,4],[215,247,32],[706,709,1],[722,735,1],[741,747,1],[749,751,2],[752,767,1],[885,900,15],[901,1014,113],[1154,1421,267],[1422,1423,1],[1542,1544,1],[1547,1550,3],[1551,1758,207],[1769,1789,20],[1790,2038,248],[2046,2047,1],[2184,2546,362],[2547,2554,7],[2555,2801,246],[2928,3059,131],[3060,3066,1],[3199,3407,208],[3449,3647,198],[3841,3843,1],[3859,3861,2],[3862,3863,1],[3866,3871,1],[3892,3896,2],[4030,4037,1],[4039,4044,1],[4046,4047,1],[4053,4056,1],[4254,4255,1],[5008,5017,1],[5741,6107,366],[6464,6622,158],[6623,6655,1],[7009,7018,1],[7028,7036,1],[8125,8127,2],[8128,8129,1],[8141,8143,1],[8157,8159,1],[8173,8175,1],[8189,8190,1],[8260,8274,14],[8314,8316,1],[8330,8332,1],[8352,8384,1],[8448,8449,1],[8451,8454,1],[8456,8457,1],[8468,8470,2],[8471,8472,1],[8478,8483,1],[8485,8489,2],[8494,8506,12],[8507,8512,5],[8513,8516,1],[8522,8525,1],[8527,8586,59],[8587,8592,5],[8593,8967,1],[8972,9e3,1],[9003,9254,1],[9280,9290,1],[9372,9449,1],[9472,10087,1],[10132,10180,1],[10183,10213,1],[10224,10626,1],[10649,10711,1],[10716,10747,1],[10750,11123,1],[11126,11157,1],[11159,11263,1],[11493,11498,1],[11856,11857,1],[11904,11929,1],[11931,12019,1],[12032,12245,1],[12272,12287,1],[12292,12306,14],[12307,12320,13],[12342,12343,1],[12350,12351,1],[12443,12444,1],[12688,12689,1],[12694,12703,1],[12736,12771,1],[12783,12800,17],[12801,12830,1],[12842,12871,1],[12880,12896,16],[12897,12927,1],[12938,12976,1],[12992,13311,1],[19904,19967,1],[42128,42182,1],[42752,42774,1],[42784,42785,1],[42889,42890,1],[43048,43051,1],[43062,43065,1],[43639,43641,1],[43867,43882,15],[43883,64297,20414],[64434,64450,1],[64832,64847,1],[64975,65020,45],[65021,65023,1],[65122,65124,2],[65125,65126,1],[65129,65284,155],[65291,65308,17],[65309,65310,1],[65342,65344,2],[65372,65374,2],[65504,65510,1],[65512,65518,1],[65532,65533,1],[65847,65855,1],[65913,65929,1],[65932,65934,1],[65936,65948,1],[65952,66e3,48],[66001,66044,1],[67703,67704,1],[68296,71487,3191],[73685,73713,1],[92988,92991,1],[92997,113820,20823],[118608,118723,1],[118784,119029,1],[119040,119078,1],[119081,119140,1],[119146,119148,1],[119171,119172,1],[119180,119209,1],[119214,119274,1],[119296,119361,1],[119365,119552,187],[119553,119638,1],[120513,120539,26],[120571,120597,26],[120629,120655,26],[120687,120713,26],[120745,120771,26],[120832,121343,1],[121399,121402,1],[121453,121460,1],[121462,121475,1],[121477,121478,1],[123215,123647,432],[126124,126128,4],[126254,126704,450],[126705,126976,271],[126977,127019,1],[127024,127123,1],[127136,127150,1],[127153,127167,1],[127169,127183,1],[127185,127221,1],[127245,127405,1],[127462,127490,1],[127504,127547,1],[127552,127560,1],[127568,127569,1],[127584,127589,1],[127744,128727,1],[128732,128748,1],[128752,128764,1],[128768,128886,1],[128891,128985,1],[128992,129003,1],[129008,129024,16],[129025,129035,1],[129040,129095,1],[129104,129113,1],[129120,129159,1],[129168,129197,1],[129200,129201,1],[129280,129619,1],[129632,129645,1],[129648,129660,1],[129664,129672,1],[129680,129725,1],[129727,129733,1],[129742,129755,1],[129760,129768,1],[129776,129784,1],[129792,129938,1],[129940,129994,1]]),_(p,"Sc",[[36,162,126],[163,165,1],[1423,1547,124],[2046,2047,1],[2546,2547,1],[2555,2801,246],[3065,3647,582],[6107,8352,2245],[8353,8384,1],[43064,65020,21956],[65129,65284,155],[65504,65505,1],[65509,65510,1],[73693,73696,1],[123647,126128,2481]]),_(p,"Sk",[[94,96,2],[168,175,7],[180,184,4],[706,709,1],[722,735,1],[741,747,1],[749,751,2],[752,767,1],[885,900,15],[901,2184,1283],[8125,8127,2],[8128,8129,1],[8141,8143,1],[8157,8159,1],[8173,8175,1],[8189,8190,1],[12443,12444,1],[42752,42774,1],[42784,42785,1],[42889,42890,1],[43867,43882,15],[43883,64434,20551],[64435,64450,1],[65342,65344,2],[65507,127995,62488],[127996,127999,1]]),_(p,"Sm",[[43,60,17],[61,62,1],[124,126,2],[172,177,5],[215,247,32],[1014,1542,528],[1543,1544,1],[8260,8274,14],[8314,8316,1],[8330,8332,1],[8472,8512,40],[8513,8516,1],[8523,8592,69],[8593,8596,1],[8602,8603,1],[8608,8614,3],[8622,8654,32],[8655,8658,3],[8660,8692,32],[8693,8959,1],[8992,8993,1],[9084,9115,31],[9116,9139,1],[9180,9185,1],[9655,9665,10],[9720,9727,1],[9839,10176,337],[10177,10180,1],[10183,10213,1],[10224,10239,1],[10496,10626,1],[10649,10711,1],[10716,10747,1],[10750,11007,1],[11056,11076,1],[11079,11084,1],[64297,65122,825],[65124,65126,1],[65291,65308,17],[65309,65310,1],[65372,65374,2],[65506,65513,7],[65514,65516,1],[120513,120539,26],[120571,120597,26],[120629,120655,26],[120687,120713,26],[120745,120771,26],[126704,126705,1]]),_(p,"So",[[166,169,3],[174,176,2],[1154,1421,267],[1422,1550,128],[1551,1758,207],[1769,1789,20],[1790,2038,248],[2554,2928,374],[3059,3064,1],[3066,3199,133],[3407,3449,42],[3841,3843,1],[3859,3861,2],[3862,3863,1],[3866,3871,1],[3892,3896,2],[4030,4037,1],[4039,4044,1],[4046,4047,1],[4053,4056,1],[4254,4255,1],[5008,5017,1],[5741,6464,723],[6622,6655,1],[7009,7018,1],[7028,7036,1],[8448,8449,1],[8451,8454,1],[8456,8457,1],[8468,8470,2],[8471,8478,7],[8479,8483,1],[8485,8489,2],[8494,8506,12],[8507,8522,15],[8524,8525,1],[8527,8586,59],[8587,8597,10],[8598,8601,1],[8604,8607,1],[8609,8610,1],[8612,8613,1],[8615,8621,1],[8623,8653,1],[8656,8657,1],[8659,8661,2],[8662,8691,1],[8960,8967,1],[8972,8991,1],[8994,9e3,1],[9003,9083,1],[9085,9114,1],[9140,9179,1],[9186,9254,1],[9280,9290,1],[9372,9449,1],[9472,9654,1],[9656,9664,1],[9666,9719,1],[9728,9838,1],[9840,10087,1],[10132,10175,1],[10240,10495,1],[11008,11055,1],[11077,11078,1],[11085,11123,1],[11126,11157,1],[11159,11263,1],[11493,11498,1],[11856,11857,1],[11904,11929,1],[11931,12019,1],[12032,12245,1],[12272,12287,1],[12292,12306,14],[12307,12320,13],[12342,12343,1],[12350,12351,1],[12688,12689,1],[12694,12703,1],[12736,12771,1],[12783,12800,17],[12801,12830,1],[12842,12871,1],[12880,12896,16],[12897,12927,1],[12938,12976,1],[12992,13311,1],[19904,19967,1],[42128,42182,1],[43048,43051,1],[43062,43063,1],[43065,43639,574],[43640,43641,1],[64832,64847,1],[64975,65021,46],[65022,65023,1],[65508,65512,4],[65517,65518,1],[65532,65533,1],[65847,65855,1],[65913,65929,1],[65932,65934,1],[65936,65948,1],[65952,66e3,48],[66001,66044,1],[67703,67704,1],[68296,71487,3191],[73685,73692,1],[73697,73713,1],[92988,92991,1],[92997,113820,20823],[118608,118723,1],[118784,119029,1],[119040,119078,1],[119081,119140,1],[119146,119148,1],[119171,119172,1],[119180,119209,1],[119214,119274,1],[119296,119361,1],[119365,119552,187],[119553,119638,1],[120832,121343,1],[121399,121402,1],[121453,121460,1],[121462,121475,1],[121477,121478,1],[123215,126124,2909],[126254,126976,722],[126977,127019,1],[127024,127123,1],[127136,127150,1],[127153,127167,1],[127169,127183,1],[127185,127221,1],[127245,127405,1],[127462,127490,1],[127504,127547,1],[127552,127560,1],[127568,127569,1],[127584,127589,1],[127744,127994,1],[128e3,128727,1],[128732,128748,1],[128752,128764,1],[128768,128886,1],[128891,128985,1],[128992,129003,1],[129008,129024,16],[129025,129035,1],[129040,129095,1],[129104,129113,1],[129120,129159,1],[129168,129197,1],[129200,129201,1],[129280,129619,1],[129632,129645,1],[129648,129660,1],[129664,129672,1],[129680,129725,1],[129727,129733,1],[129742,129755,1],[129760,129768,1],[129776,129784,1],[129792,129938,1],[129940,129994,1]]),_(p,"Z",[[32,160,128],[5760,8192,2432],[8193,8202,1],[8232,8233,1],[8239,8287,48],[12288,12288,1]]),_(p,"Zl",[[8232,8232,1]]),_(p,"Zp",[[8233,8233,1]]),_(p,"Zs",[[32,160,128],[5760,8192,2432],[8193,8202,1],[8239,8287,48],[12288,12288,1]]),_(p,"Adlam",[[125184,125259,1],[125264,125273,1],[125278,125279,1]]),_(p,"Ahom",[[71424,71450,1],[71453,71467,1],[71472,71494,1]]),_(p,"Anatolian_Hieroglyphs",[[82944,83526,1]]),_(p,"Arabic",[[1536,1540,1],[1542,1547,1],[1549,1562,1],[1564,1566,1],[1568,1599,1],[1601,1610,1],[1622,1647,1],[1649,1756,1],[1758,1791,1],[1872,1919,1],[2160,2190,1],[2192,2193,1],[2200,2273,1],[2275,2303,1],[64336,64450,1],[64467,64829,1],[64832,64911,1],[64914,64967,1],[64975,65008,33],[65009,65023,1],[65136,65140,1],[65142,65276,1],[69216,69246,1],[69373,69375,1],[126464,126467,1],[126469,126495,1],[126497,126498,1],[126500,126503,3],[126505,126514,1],[126516,126519,1],[126521,126523,2],[126530,126535,5],[126537,126541,2],[126542,126543,1],[126545,126546,1],[126548,126551,3],[126553,126561,2],[126562,126564,2],[126567,126570,1],[126572,126578,1],[126580,126583,1],[126585,126588,1],[126590,126592,2],[126593,126601,1],[126603,126619,1],[126625,126627,1],[126629,126633,1],[126635,126651,1],[126704,126705,1]]),_(p,"Armenian",[[1329,1366,1],[1369,1418,1],[1421,1423,1],[64275,64279,1]]),_(p,"Avestan",[[68352,68405,1],[68409,68415,1]]),_(p,"Balinese",[[6912,6988,1],[6992,7038,1]]),_(p,"Bamum",[[42656,42743,1],[92160,92728,1]]),_(p,"Bassa_Vah",[[92880,92909,1],[92912,92917,1]]),_(p,"Batak",[[7104,7155,1],[7164,7167,1]]),_(p,"Bengali",[[2432,2435,1],[2437,2444,1],[2447,2448,1],[2451,2472,1],[2474,2480,1],[2482,2486,4],[2487,2489,1],[2492,2500,1],[2503,2504,1],[2507,2510,1],[2519,2524,5],[2525,2527,2],[2528,2531,1],[2534,2558,1]]),_(p,"Bhaiksuki",[[72704,72712,1],[72714,72758,1],[72760,72773,1],[72784,72812,1]]),_(p,"Bopomofo",[[746,747,1],[12549,12591,1],[12704,12735,1]]),_(p,"Brahmi",[[69632,69709,1],[69714,69749,1],[69759,69759,1]]),_(p,"Braille",[[10240,10495,1]]),_(p,"Buginese",[[6656,6683,1],[6686,6687,1]]),_(p,"Buhid",[[5952,5971,1]]),_(p,"Canadian_Aboriginal",[[5120,5759,1],[6320,6389,1],[72368,72383,1]]),_(p,"Carian",[[66208,66256,1]]),_(p,"Caucasian_Albanian",[[66864,66915,1],[66927,66927,1]]),_(p,"Chakma",[[69888,69940,1],[69942,69959,1]]),_(p,"Cham",[[43520,43574,1],[43584,43597,1],[43600,43609,1],[43612,43615,1]]),_(p,"Cherokee",[[5024,5109,1],[5112,5117,1],[43888,43967,1]]),_(p,"Chorasmian",[[69552,69579,1]]),_(p,"Common",[[0,64,1],[91,96,1],[123,169,1],[171,185,1],[187,191,1],[215,247,32],[697,735,1],[741,745,1],[748,767,1],[884,894,10],[901,903,2],[1541,1548,7],[1563,1567,4],[1600,1757,157],[2274,2404,130],[2405,3647,1242],[4053,4056,1],[4347,5867,1520],[5868,5869,1],[5941,5942,1],[6146,6147,1],[6149,7379,1230],[7393,7401,8],[7402,7404,1],[7406,7411,1],[7413,7415,1],[7418,8192,774],[8193,8203,1],[8206,8292,1],[8294,8304,1],[8308,8318,1],[8320,8334,1],[8352,8384,1],[8448,8485,1],[8487,8489,1],[8492,8497,1],[8499,8525,1],[8527,8543,1],[8585,8587,1],[8592,9254,1],[9280,9290,1],[9312,10239,1],[10496,11123,1],[11126,11157,1],[11159,11263,1],[11776,11869,1],[12272,12292,1],[12294,12296,2],[12297,12320,1],[12336,12343,1],[12348,12351,1],[12443,12444,1],[12448,12539,91],[12540,12688,148],[12689,12703,1],[12736,12771,1],[12783,12832,49],[12833,12895,1],[12927,13007,1],[13055,13144,89],[13145,13311,1],[19904,19967,1],[42752,42785,1],[42888,42890,1],[43056,43065,1],[43310,43471,161],[43867,43882,15],[43883,64830,20947],[64831,65040,209],[65041,65049,1],[65072,65106,1],[65108,65126,1],[65128,65131,1],[65279,65281,2],[65282,65312,1],[65339,65344,1],[65371,65381,1],[65392,65438,46],[65439,65504,65],[65505,65510,1],[65512,65518,1],[65529,65533,1],[65792,65794,1],[65799,65843,1],[65847,65855,1],[65936,65948,1],[66e3,66044,1],[66273,66299,1],[113824,113827,1],[118608,118723,1],[118784,119029,1],[119040,119078,1],[119081,119142,1],[119146,119162,1],[119171,119172,1],[119180,119209,1],[119214,119274,1],[119488,119507,1],[119520,119539,1],[119552,119638,1],[119648,119672,1],[119808,119892,1],[119894,119964,1],[119966,119967,1],[119970,119973,3],[119974,119977,3],[119978,119980,1],[119982,119993,1],[119995,119997,2],[119998,120003,1],[120005,120069,1],[120071,120074,1],[120077,120084,1],[120086,120092,1],[120094,120121,1],[120123,120126,1],[120128,120132,1],[120134,120138,4],[120139,120144,1],[120146,120485,1],[120488,120779,1],[120782,120831,1],[126065,126132,1],[126209,126269,1],[126976,127019,1],[127024,127123,1],[127136,127150,1],[127153,127167,1],[127169,127183,1],[127185,127221,1],[127232,127405,1],[127462,127487,1],[127489,127490,1],[127504,127547,1],[127552,127560,1],[127568,127569,1],[127584,127589,1],[127744,128727,1],[128732,128748,1],[128752,128764,1],[128768,128886,1],[128891,128985,1],[128992,129003,1],[129008,129024,16],[129025,129035,1],[129040,129095,1],[129104,129113,1],[129120,129159,1],[129168,129197,1],[129200,129201,1],[129280,129619,1],[129632,129645,1],[129648,129660,1],[129664,129672,1],[129680,129725,1],[129727,129733,1],[129742,129755,1],[129760,129768,1],[129776,129784,1],[129792,129938,1],[129940,129994,1],[130032,130041,1],[917505,917536,31],[917537,917631,1]]),_(p,"foldCommon",[[924,956,32]]),_(p,"Coptic",[[994,1007,1],[11392,11507,1],[11513,11519,1]]),_(p,"Cuneiform",[[73728,74649,1],[74752,74862,1],[74864,74868,1],[74880,75075,1]]),_(p,"Cypriot",[[67584,67589,1],[67592,67594,2],[67595,67637,1],[67639,67640,1],[67644,67647,3]]),_(p,"Cypro_Minoan",[[77712,77810,1]]),_(p,"Cyrillic",[[1024,1156,1],[1159,1327,1],[7296,7304,1],[7467,7544,77],[11744,11775,1],[42560,42655,1],[65070,65071,1],[122928,122989,1],[123023,123023,1]]),_(p,"Deseret",[[66560,66639,1]]),_(p,"Devanagari",[[2304,2384,1],[2389,2403,1],[2406,2431,1],[43232,43263,1],[72448,72457,1]]),_(p,"Dives_Akuru",[[71936,71942,1],[71945,71948,3],[71949,71955,1],[71957,71958,1],[71960,71989,1],[71991,71992,1],[71995,72006,1],[72016,72025,1]]),_(p,"Dogra",[[71680,71739,1]]),_(p,"Duployan",[[113664,113770,1],[113776,113788,1],[113792,113800,1],[113808,113817,1],[113820,113823,1]]),_(p,"Egyptian_Hieroglyphs",[[77824,78933,1]]),_(p,"Elbasan",[[66816,66855,1]]),_(p,"Elymaic",[[69600,69622,1]]),_(p,"Ethiopic",[[4608,4680,1],[4682,4685,1],[4688,4694,1],[4696,4698,2],[4699,4701,1],[4704,4744,1],[4746,4749,1],[4752,4784,1],[4786,4789,1],[4792,4798,1],[4800,4802,2],[4803,4805,1],[4808,4822,1],[4824,4880,1],[4882,4885,1],[4888,4954,1],[4957,4988,1],[4992,5017,1],[11648,11670,1],[11680,11686,1],[11688,11694,1],[11696,11702,1],[11704,11710,1],[11712,11718,1],[11720,11726,1],[11728,11734,1],[11736,11742,1],[43777,43782,1],[43785,43790,1],[43793,43798,1],[43808,43814,1],[43816,43822,1],[124896,124902,1],[124904,124907,1],[124909,124910,1],[124912,124926,1]]),_(p,"Georgian",[[4256,4293,1],[4295,4301,6],[4304,4346,1],[4348,4351,1],[7312,7354,1],[7357,7359,1],[11520,11557,1],[11559,11565,6]]),_(p,"Glagolitic",[[11264,11359,1],[122880,122886,1],[122888,122904,1],[122907,122913,1],[122915,122916,1],[122918,122922,1]]),_(p,"Gothic",[[66352,66378,1]]),_(p,"Grantha",[[70400,70403,1],[70405,70412,1],[70415,70416,1],[70419,70440,1],[70442,70448,1],[70450,70451,1],[70453,70457,1],[70460,70468,1],[70471,70472,1],[70475,70477,1],[70480,70487,7],[70493,70499,1],[70502,70508,1],[70512,70516,1]]),_(p,"Greek",[[880,883,1],[885,887,1],[890,893,1],[895,900,5],[902,904,2],[905,906,1],[908,910,2],[911,929,1],[931,993,1],[1008,1023,1],[7462,7466,1],[7517,7521,1],[7526,7530,1],[7615,7936,321],[7937,7957,1],[7960,7965,1],[7968,8005,1],[8008,8013,1],[8016,8023,1],[8025,8031,2],[8032,8061,1],[8064,8116,1],[8118,8132,1],[8134,8147,1],[8150,8155,1],[8157,8175,1],[8178,8180,1],[8182,8190,1],[8486,43877,35391],[65856,65934,1],[65952,119296,53344],[119297,119365,1]]),_(p,"foldGreek",[[181,837,656]]),_(p,"Gujarati",[[2689,2691,1],[2693,2701,1],[2703,2705,1],[2707,2728,1],[2730,2736,1],[2738,2739,1],[2741,2745,1],[2748,2757,1],[2759,2761,1],[2763,2765,1],[2768,2784,16],[2785,2787,1],[2790,2801,1],[2809,2815,1]]),_(p,"Gunjala_Gondi",[[73056,73061,1],[73063,73064,1],[73066,73102,1],[73104,73105,1],[73107,73112,1],[73120,73129,1]]),_(p,"Gurmukhi",[[2561,2563,1],[2565,2570,1],[2575,2576,1],[2579,2600,1],[2602,2608,1],[2610,2611,1],[2613,2614,1],[2616,2617,1],[2620,2622,2],[2623,2626,1],[2631,2632,1],[2635,2637,1],[2641,2649,8],[2650,2652,1],[2654,2662,8],[2663,2678,1]]),_(p,"Han",[[11904,11929,1],[11931,12019,1],[12032,12245,1],[12293,12295,2],[12321,12329,1],[12344,12347,1],[13312,19903,1],[19968,40959,1],[63744,64109,1],[64112,64217,1],[94178,94179,1],[94192,94193,1],[131072,173791,1],[173824,177977,1],[177984,178205,1],[178208,183969,1],[183984,191456,1],[191472,192093,1],[194560,195101,1],[196608,201546,1],[201552,205743,1]]),_(p,"Hangul",[[4352,4607,1],[12334,12335,1],[12593,12686,1],[12800,12830,1],[12896,12926,1],[43360,43388,1],[44032,55203,1],[55216,55238,1],[55243,55291,1],[65440,65470,1],[65474,65479,1],[65482,65487,1],[65490,65495,1],[65498,65500,1]]),_(p,"Hanifi_Rohingya",[[68864,68903,1],[68912,68921,1]]),_(p,"Hanunoo",[[5920,5940,1]]),_(p,"Hatran",[[67808,67826,1],[67828,67829,1],[67835,67839,1]]),_(p,"Hebrew",[[1425,1479,1],[1488,1514,1],[1519,1524,1],[64285,64310,1],[64312,64316,1],[64318,64320,2],[64321,64323,2],[64324,64326,2],[64327,64335,1]]),_(p,"Hiragana",[[12353,12438,1],[12445,12447,1],[110593,110879,1],[110898,110928,30],[110929,110930,1],[127488,127488,1]]),_(p,"Imperial_Aramaic",[[67648,67669,1],[67671,67679,1]]),_(p,"Inherited",[[768,879,1],[1157,1158,1],[1611,1621,1],[1648,2385,737],[2386,2388,1],[6832,6862,1],[7376,7378,1],[7380,7392,1],[7394,7400,1],[7405,7412,7],[7416,7417,1],[7616,7679,1],[8204,8205,1],[8400,8432,1],[12330,12333,1],[12441,12442,1],[65024,65039,1],[65056,65069,1],[66045,66272,227],[70459,118528,48069],[118529,118573,1],[118576,118598,1],[119143,119145,1],[119163,119170,1],[119173,119179,1],[119210,119213,1],[917760,917999,1]]),_(p,"foldInherited",[[921,953,32],[8126,8126,1]]),_(p,"Inscriptional_Pahlavi",[[68448,68466,1],[68472,68479,1]]),_(p,"Inscriptional_Parthian",[[68416,68437,1],[68440,68447,1]]),_(p,"Javanese",[[43392,43469,1],[43472,43481,1],[43486,43487,1]]),_(p,"Kaithi",[[69760,69826,1],[69837,69837,1]]),_(p,"Kannada",[[3200,3212,1],[3214,3216,1],[3218,3240,1],[3242,3251,1],[3253,3257,1],[3260,3268,1],[3270,3272,1],[3274,3277,1],[3285,3286,1],[3293,3294,1],[3296,3299,1],[3302,3311,1],[3313,3315,1]]),_(p,"Katakana",[[12449,12538,1],[12541,12543,1],[12784,12799,1],[13008,13054,1],[13056,13143,1],[65382,65391,1],[65393,65437,1],[110576,110579,1],[110581,110587,1],[110589,110590,1],[110592,110880,288],[110881,110882,1],[110933,110948,15],[110949,110951,1]]),_(p,"Kawi",[[73472,73488,1],[73490,73530,1],[73534,73561,1]]),_(p,"Kayah_Li",[[43264,43309,1],[43311,43311,1]]),_(p,"Kharoshthi",[[68096,68099,1],[68101,68102,1],[68108,68115,1],[68117,68119,1],[68121,68149,1],[68152,68154,1],[68159,68168,1],[68176,68184,1]]),_(p,"Khitan_Small_Script",[[94180,101120,6940],[101121,101589,1]]),_(p,"Khmer",[[6016,6109,1],[6112,6121,1],[6128,6137,1],[6624,6655,1]]),_(p,"Khojki",[[70144,70161,1],[70163,70209,1]]),_(p,"Khudawadi",[[70320,70378,1],[70384,70393,1]]),_(p,"Lao",[[3713,3714,1],[3716,3718,2],[3719,3722,1],[3724,3747,1],[3749,3751,2],[3752,3773,1],[3776,3780,1],[3782,3784,2],[3785,3790,1],[3792,3801,1],[3804,3807,1]]),_(p,"Latin",[[65,90,1],[97,122,1],[170,186,16],[192,214,1],[216,246,1],[248,696,1],[736,740,1],[7424,7461,1],[7468,7516,1],[7522,7525,1],[7531,7543,1],[7545,7614,1],[7680,7935,1],[8305,8319,14],[8336,8348,1],[8490,8491,1],[8498,8526,28],[8544,8584,1],[11360,11391,1],[42786,42887,1],[42891,42954,1],[42960,42961,1],[42963,42965,2],[42966,42969,1],[42994,43007,1],[43824,43866,1],[43868,43876,1],[43878,43881,1],[64256,64262,1],[65313,65338,1],[65345,65370,1],[67456,67461,1],[67463,67504,1],[67506,67514,1],[122624,122654,1],[122661,122666,1]]),_(p,"Lepcha",[[7168,7223,1],[7227,7241,1],[7245,7247,1]]),_(p,"Limbu",[[6400,6430,1],[6432,6443,1],[6448,6459,1],[6464,6468,4],[6469,6479,1]]),_(p,"Linear_A",[[67072,67382,1],[67392,67413,1],[67424,67431,1]]),_(p,"Linear_B",[[65536,65547,1],[65549,65574,1],[65576,65594,1],[65596,65597,1],[65599,65613,1],[65616,65629,1],[65664,65786,1]]),_(p,"Lisu",[[42192,42239,1],[73648,73648,1]]),_(p,"Lycian",[[66176,66204,1]]),_(p,"Lydian",[[67872,67897,1],[67903,67903,1]]),_(p,"Mahajani",[[69968,70006,1]]),_(p,"Makasar",[[73440,73464,1]]),_(p,"Malayalam",[[3328,3340,1],[3342,3344,1],[3346,3396,1],[3398,3400,1],[3402,3407,1],[3412,3427,1],[3430,3455,1]]),_(p,"Mandaic",[[2112,2139,1],[2142,2142,1]]),_(p,"Manichaean",[[68288,68326,1],[68331,68342,1]]),_(p,"Marchen",[[72816,72847,1],[72850,72871,1],[72873,72886,1]]),_(p,"Masaram_Gondi",[[72960,72966,1],[72968,72969,1],[72971,73014,1],[73018,73020,2],[73021,73023,2],[73024,73031,1],[73040,73049,1]]),_(p,"Medefaidrin",[[93760,93850,1]]),_(p,"Meetei_Mayek",[[43744,43766,1],[43968,44013,1],[44016,44025,1]]),_(p,"Mende_Kikakui",[[124928,125124,1],[125127,125142,1]]),_(p,"Meroitic_Cursive",[[68e3,68023,1],[68028,68047,1],[68050,68095,1]]),_(p,"Meroitic_Hieroglyphs",[[67968,67999,1]]),_(p,"Miao",[[93952,94026,1],[94031,94087,1],[94095,94111,1]]),_(p,"Modi",[[71168,71236,1],[71248,71257,1]]),_(p,"Mongolian",[[6144,6145,1],[6148,6150,2],[6151,6169,1],[6176,6264,1],[6272,6314,1],[71264,71276,1]]),_(p,"Mro",[[92736,92766,1],[92768,92777,1],[92782,92783,1]]),_(p,"Multani",[[70272,70278,1],[70280,70282,2],[70283,70285,1],[70287,70301,1],[70303,70313,1]]),_(p,"Myanmar",[[4096,4255,1],[43488,43518,1],[43616,43647,1]]),_(p,"Nabataean",[[67712,67742,1],[67751,67759,1]]),_(p,"Nag_Mundari",[[124112,124153,1]]),_(p,"Nandinagari",[[72096,72103,1],[72106,72151,1],[72154,72164,1]]),_(p,"New_Tai_Lue",[[6528,6571,1],[6576,6601,1],[6608,6618,1],[6622,6623,1]]),_(p,"Newa",[[70656,70747,1],[70749,70753,1]]),_(p,"Nko",[[1984,2042,1],[2045,2047,1]]),_(p,"Nushu",[[94177,110960,16783],[110961,111355,1]]),_(p,"Nyiakeng_Puachue_Hmong",[[123136,123180,1],[123184,123197,1],[123200,123209,1],[123214,123215,1]]),_(p,"Ogham",[[5760,5788,1]]),_(p,"Ol_Chiki",[[7248,7295,1]]),_(p,"Old_Hungarian",[[68736,68786,1],[68800,68850,1],[68858,68863,1]]),_(p,"Old_Italic",[[66304,66339,1],[66349,66351,1]]),_(p,"Old_North_Arabian",[[68224,68255,1]]),_(p,"Old_Permic",[[66384,66426,1]]),_(p,"Old_Persian",[[66464,66499,1],[66504,66517,1]]),_(p,"Old_Sogdian",[[69376,69415,1]]),_(p,"Old_South_Arabian",[[68192,68223,1]]),_(p,"Old_Turkic",[[68608,68680,1]]),_(p,"Old_Uyghur",[[69488,69513,1]]),_(p,"Oriya",[[2817,2819,1],[2821,2828,1],[2831,2832,1],[2835,2856,1],[2858,2864,1],[2866,2867,1],[2869,2873,1],[2876,2884,1],[2887,2888,1],[2891,2893,1],[2901,2903,1],[2908,2909,1],[2911,2915,1],[2918,2935,1]]),_(p,"Osage",[[66736,66771,1],[66776,66811,1]]),_(p,"Osmanya",[[66688,66717,1],[66720,66729,1]]),_(p,"Pahawh_Hmong",[[92928,92997,1],[93008,93017,1],[93019,93025,1],[93027,93047,1],[93053,93071,1]]),_(p,"Palmyrene",[[67680,67711,1]]),_(p,"Pau_Cin_Hau",[[72384,72440,1]]),_(p,"Phags_Pa",[[43072,43127,1]]),_(p,"Phoenician",[[67840,67867,1],[67871,67871,1]]),_(p,"Psalter_Pahlavi",[[68480,68497,1],[68505,68508,1],[68521,68527,1]]),_(p,"Rejang",[[43312,43347,1],[43359,43359,1]]),_(p,"Runic",[[5792,5866,1],[5870,5880,1]]),_(p,"Samaritan",[[2048,2093,1],[2096,2110,1]]),_(p,"Saurashtra",[[43136,43205,1],[43214,43225,1]]),_(p,"Sharada",[[70016,70111,1]]),_(p,"Shavian",[[66640,66687,1]]),_(p,"Siddham",[[71040,71093,1],[71096,71133,1]]),_(p,"SignWriting",[[120832,121483,1],[121499,121503,1],[121505,121519,1]]),_(p,"Sinhala",[[3457,3459,1],[3461,3478,1],[3482,3505,1],[3507,3515,1],[3517,3520,3],[3521,3526,1],[3530,3535,5],[3536,3540,1],[3542,3544,2],[3545,3551,1],[3558,3567,1],[3570,3572,1],[70113,70132,1]]),_(p,"Sogdian",[[69424,69465,1]]),_(p,"Sora_Sompeng",[[69840,69864,1],[69872,69881,1]]),_(p,"Soyombo",[[72272,72354,1]]),_(p,"Sundanese",[[7040,7103,1],[7360,7367,1]]),_(p,"Syloti_Nagri",[[43008,43052,1]]),_(p,"Syriac",[[1792,1805,1],[1807,1866,1],[1869,1871,1],[2144,2154,1]]),_(p,"Tagalog",[[5888,5909,1],[5919,5919,1]]),_(p,"Tagbanwa",[[5984,5996,1],[5998,6e3,1],[6002,6003,1]]),_(p,"Tai_Le",[[6480,6509,1],[6512,6516,1]]),_(p,"Tai_Tham",[[6688,6750,1],[6752,6780,1],[6783,6793,1],[6800,6809,1],[6816,6829,1]]),_(p,"Tai_Viet",[[43648,43714,1],[43739,43743,1]]),_(p,"Takri",[[71296,71353,1],[71360,71369,1]]),_(p,"Tamil",[[2946,2947,1],[2949,2954,1],[2958,2960,1],[2962,2965,1],[2969,2970,1],[2972,2974,2],[2975,2979,4],[2980,2984,4],[2985,2986,1],[2990,3001,1],[3006,3010,1],[3014,3016,1],[3018,3021,1],[3024,3031,7],[3046,3066,1],[73664,73713,1],[73727,73727,1]]),_(p,"Tangsa",[[92784,92862,1],[92864,92873,1]]),_(p,"Tangut",[[94176,94208,32],[94209,100343,1],[100352,101119,1],[101632,101640,1]]),_(p,"Telugu",[[3072,3084,1],[3086,3088,1],[3090,3112,1],[3114,3129,1],[3132,3140,1],[3142,3144,1],[3146,3149,1],[3157,3158,1],[3160,3162,1],[3165,3168,3],[3169,3171,1],[3174,3183,1],[3191,3199,1]]),_(p,"Thaana",[[1920,1969,1]]),_(p,"Thai",[[3585,3642,1],[3648,3675,1]]),_(p,"Tibetan",[[3840,3911,1],[3913,3948,1],[3953,3991,1],[3993,4028,1],[4030,4044,1],[4046,4052,1],[4057,4058,1]]),_(p,"Tifinagh",[[11568,11623,1],[11631,11632,1],[11647,11647,1]]),_(p,"Tirhuta",[[70784,70855,1],[70864,70873,1]]),_(p,"Toto",[[123536,123566,1]]),_(p,"Ugaritic",[[66432,66461,1],[66463,66463,1]]),_(p,"Vai",[[42240,42539,1]]),_(p,"Vithkuqi",[[66928,66938,1],[66940,66954,1],[66956,66962,1],[66964,66965,1],[66967,66977,1],[66979,66993,1],[66995,67001,1],[67003,67004,1]]),_(p,"Wancho",[[123584,123641,1],[123647,123647,1]]),_(p,"Warang_Citi",[[71840,71922,1],[71935,71935,1]]),_(p,"Yezidi",[[69248,69289,1],[69291,69293,1],[69296,69297,1]]),_(p,"Yi",[[40960,42124,1],[42128,42182,1]]),_(p,"Zanabazar_Square",[[72192,72263,1]]),_(p,"CATEGORIES",new Map([["C",p.C],["Cc",p.Cc],["Cf",p.Cf],["Co",p.Co],["Cs",p.Cs],["L",p.L],["Ll",p.Ll],["Lm",p.Lm],["Lo",p.Lo],["Lt",p.Lt],["Lu",p.Lu],["M",p.M],["Mc",p.Mc],["Me",p.Me],["Mn",p.Mn],["N",p.N],["Nd",p.Nd],["Nl",p.Nl],["No",p.No],["P",p.P],["Pc",p.Pc],["Pd",p.Pd],["Pe",p.Pe],["Pf",p.Pf],["Pi",p.Pi],["Po",p.Po],["Ps",p.Ps],["S",p.S],["Sc",p.Sc],["Sk",p.Sk],["Sm",p.Sm],["So",p.So],["Z",p.Z],["Zl",p.Zl],["Zp",p.Zp],["Zs",p.Zs]])),_(p,"SCRIPTS",new Map([["Adlam",p.Adlam],["Ahom",p.Ahom],["Anatolian_Hieroglyphs",p.Anatolian_Hieroglyphs],["Arabic",p.Arabic],["Armenian",p.Armenian],["Avestan",p.Avestan],["Balinese",p.Balinese],["Bamum",p.Bamum],["Bassa_Vah",p.Bassa_Vah],["Batak",p.Batak],["Bengali",p.Bengali],["Bhaiksuki",p.Bhaiksuki],["Bopomofo",p.Bopomofo],["Brahmi",p.Brahmi],["Braille",p.Braille],["Buginese",p.Buginese],["Buhid",p.Buhid],["Canadian_Aboriginal",p.Canadian_Aboriginal],["Carian",p.Carian],["Caucasian_Albanian",p.Caucasian_Albanian],["Chakma",p.Chakma],["Cham",p.Cham],["Cherokee",p.Cherokee],["Chorasmian",p.Chorasmian],["Common",p.Common],["Coptic",p.Coptic],["Cuneiform",p.Cuneiform],["Cypriot",p.Cypriot],["Cypro_Minoan",p.Cypro_Minoan],["Cyrillic",p.Cyrillic],["Deseret",p.Deseret],["Devanagari",p.Devanagari],["Dives_Akuru",p.Dives_Akuru],["Dogra",p.Dogra],["Duployan",p.Duployan],["Egyptian_Hieroglyphs",p.Egyptian_Hieroglyphs],["Elbasan",p.Elbasan],["Elymaic",p.Elymaic],["Ethiopic",p.Ethiopic],["Georgian",p.Georgian],["Glagolitic",p.Glagolitic],["Gothic",p.Gothic],["Grantha",p.Grantha],["Greek",p.Greek],["Gujarati",p.Gujarati],["Gunjala_Gondi",p.Gunjala_Gondi],["Gurmukhi",p.Gurmukhi],["Han",p.Han],["Hangul",p.Hangul],["Hanifi_Rohingya",p.Hanifi_Rohingya],["Hanunoo",p.Hanunoo],["Hatran",p.Hatran],["Hebrew",p.Hebrew],["Hiragana",p.Hiragana],["Imperial_Aramaic",p.Imperial_Aramaic],["Inherited",p.Inherited],["Inscriptional_Pahlavi",p.Inscriptional_Pahlavi],["Inscriptional_Parthian",p.Inscriptional_Parthian],["Javanese",p.Javanese],["Kaithi",p.Kaithi],["Kannada",p.Kannada],["Katakana",p.Katakana],["Kawi",p.Kawi],["Kayah_Li",p.Kayah_Li],["Kharoshthi",p.Kharoshthi],["Khitan_Small_Script",p.Khitan_Small_Script],["Khmer",p.Khmer],["Khojki",p.Khojki],["Khudawadi",p.Khudawadi],["Lao",p.Lao],["Latin",p.Latin],["Lepcha",p.Lepcha],["Limbu",p.Limbu],["Linear_A",p.Linear_A],["Linear_B",p.Linear_B],["Lisu",p.Lisu],["Lycian",p.Lycian],["Lydian",p.Lydian],["Mahajani",p.Mahajani],["Makasar",p.Makasar],["Malayalam",p.Malayalam],["Mandaic",p.Mandaic],["Manichaean",p.Manichaean],["Marchen",p.Marchen],["Masaram_Gondi",p.Masaram_Gondi],["Medefaidrin",p.Medefaidrin],["Meetei_Mayek",p.Meetei_Mayek],["Mende_Kikakui",p.Mende_Kikakui],["Meroitic_Cursive",p.Meroitic_Cursive],["Meroitic_Hieroglyphs",p.Meroitic_Hieroglyphs],["Miao",p.Miao],["Modi",p.Modi],["Mongolian",p.Mongolian],["Mro",p.Mro],["Multani",p.Multani],["Myanmar",p.Myanmar],["Nabataean",p.Nabataean],["Nag_Mundari",p.Nag_Mundari],["Nandinagari",p.Nandinagari],["New_Tai_Lue",p.New_Tai_Lue],["Newa",p.Newa],["Nko",p.Nko],["Nushu",p.Nushu],["Nyiakeng_Puachue_Hmong",p.Nyiakeng_Puachue_Hmong],["Ogham",p.Ogham],["Ol_Chiki",p.Ol_Chiki],["Old_Hungarian",p.Old_Hungarian],["Old_Italic",p.Old_Italic],["Old_North_Arabian",p.Old_North_Arabian],["Old_Permic",p.Old_Permic],["Old_Persian",p.Old_Persian],["Old_Sogdian",p.Old_Sogdian],["Old_South_Arabian",p.Old_South_Arabian],["Old_Turkic",p.Old_Turkic],["Old_Uyghur",p.Old_Uyghur],["Oriya",p.Oriya],["Osage",p.Osage],["Osmanya",p.Osmanya],["Pahawh_Hmong",p.Pahawh_Hmong],["Palmyrene",p.Palmyrene],["Pau_Cin_Hau",p.Pau_Cin_Hau],["Phags_Pa",p.Phags_Pa],["Phoenician",p.Phoenician],["Psalter_Pahlavi",p.Psalter_Pahlavi],["Rejang",p.Rejang],["Runic",p.Runic],["Samaritan",p.Samaritan],["Saurashtra",p.Saurashtra],["Sharada",p.Sharada],["Shavian",p.Shavian],["Siddham",p.Siddham],["SignWriting",p.SignWriting],["Sinhala",p.Sinhala],["Sogdian",p.Sogdian],["Sora_Sompeng",p.Sora_Sompeng],["Soyombo",p.Soyombo],["Sundanese",p.Sundanese],["Syloti_Nagri",p.Syloti_Nagri],["Syriac",p.Syriac],["Tagalog",p.Tagalog],["Tagbanwa",p.Tagbanwa],["Tai_Le",p.Tai_Le],["Tai_Tham",p.Tai_Tham],["Tai_Viet",p.Tai_Viet],["Takri",p.Takri],["Tamil",p.Tamil],["Tangsa",p.Tangsa],["Tangut",p.Tangut],["Telugu",p.Telugu],["Thaana",p.Thaana],["Thai",p.Thai],["Tibetan",p.Tibetan],["Tifinagh",p.Tifinagh],["Tirhuta",p.Tirhuta],["Toto",p.Toto],["Ugaritic",p.Ugaritic],["Vai",p.Vai],["Vithkuqi",p.Vithkuqi],["Wancho",p.Wancho],["Warang_Citi",p.Warang_Citi],["Yezidi",p.Yezidi],["Yi",p.Yi],["Zanabazar_Square",p.Zanabazar_Square]])),_(p,"FOLD_CATEGORIES",new Map([["L",p.foldL],["Ll",p.foldLl],["Lt",p.foldLt],["Lu",p.foldLu],["M",p.foldM],["Mn",p.foldMn]])),_(p,"FOLD_SCRIPT",new Map([["Common",p.foldCommon],["Greek",p.foldGreek],["Inherited",p.foldInherited]]));let Je=p;class J{static is32(e,t){let n=0,s=e.length;for(;n<s;){let i=n+Math.floor((s-n)/2),o=e[i];if(o[0]<=t&&t<=o[1])return(t-o[0])%o[2]===0;t<o[0]?s=i:n=i+1}return!1}static is(e,t){if(t<=this.MAX_LATIN1){for(let n of e)if(!(t>n[1]))return t<n[0]?!1:(t-n[0])%n[2]===0;return!1}return e.length>0&&t>=e[0][0]&&this.is32(e,t)}static isUpper(e){if(e<=this.MAX_LATIN1){const t=String.fromCodePoint(e);return t.toUpperCase()===t&&t.toLowerCase()!==t}return this.is(Je.Upper,e)}static isPrint(e){return e<=this.MAX_LATIN1?e>=32&&e<127||e>=161&&e!==173:this.is(Je.L,e)||this.is(Je.M,e)||this.is(Je.N,e)||this.is(Je.P,e)||this.is(Je.S,e)}static simpleFold(e){if(Je.CASE_ORBIT.has(e))return Je.CASE_ORBIT.get(e);const t=V.toLowerCase(e);return t!==e?t:V.toUpperCase(e)}static equalsIgnoreCase(e,t){if(e<0||t<0||e===t)return!0;if(e<=this.MAX_ASCII&&t<=this.MAX_ASCII)return V.CODES.get("A")<=e&&e<=V.CODES.get("Z")&&(e|=32),V.CODES.get("A")<=t&&t<=V.CODES.get("Z")&&(t|=32),e===t;for(let n=this.simpleFold(e);n!==e;n=this.simpleFold(n))if(n===t)return!0;return!1}}_(J,"MAX_RUNE",1114111),_(J,"MAX_ASCII",127),_(J,"MAX_LATIN1",255),_(J,"MAX_BMP",65535),_(J,"MIN_FOLD",65),_(J,"MAX_FOLD",125251);class re{static emptyInts(){return[]}static isalnum(e){return V.CODES.get("0")<=e&&e<=V.CODES.get("9")||V.CODES.get("a")<=e&&e<=V.CODES.get("z")||V.CODES.get("A")<=e&&e<=V.CODES.get("Z")}static unhex(e){return V.CODES.get("0")<=e&&e<=V.CODES.get("9")?e-V.CODES.get("0"):V.CODES.get("a")<=e&&e<=V.CODES.get("f")?e-V.CODES.get("a")+10:V.CODES.get("A")<=e&&e<=V.CODES.get("F")?e-V.CODES.get("A")+10:-1}static escapeRune(e){let t="";if(J.isPrint(e))this.METACHARACTERS.indexOf(String.fromCodePoint(e))>=0&&(t+="\\"),t+=String.fromCodePoint(e);else switch(e){case V.CODES.get('"'):t+='\\"';break;case V.CODES.get("\\"):t+="\\\\";break;case V.CODES.get("	"):t+="\\t";break;case V.CODES.get(`
`):t+="\\n";break;case V.CODES.get("\r"):t+="\\r";break;case V.CODES.get("\b"):t+="\\b";break;case V.CODES.get("\f"):t+="\\f";break;default:{let n=e.toString(16);e<256?(t+="\\x",n.length===1&&(t+="0"),t+=n):t+=`\\x{${n}}`;break}}return t}static stringToRunes(e){return String(e).split("").map(t=>t.codePointAt(0))}static runeToString(e){return String.fromCodePoint(e)}static isWordRune(e){return V.CODES.get("a")<=e&&e<=V.CODES.get("z")||V.CODES.get("A")<=e&&e<=V.CODES.get("Z")||V.CODES.get("0")<=e&&e<=V.CODES.get("9")||e===V.CODES.get("_")}static emptyOpContext(e,t){let n=0;return e<0&&(n|=this.EMPTY_BEGIN_TEXT|this.EMPTY_BEGIN_LINE),e===V.CODES.get(`
`)&&(n|=this.EMPTY_BEGIN_LINE),t<0&&(n|=this.EMPTY_END_TEXT|this.EMPTY_END_LINE),t===V.CODES.get(`
`)&&(n|=this.EMPTY_END_LINE),this.isWordRune(e)!==this.isWordRune(t)?n|=this.EMPTY_WORD_BOUNDARY:n|=this.EMPTY_NO_WORD_BOUNDARY,n}static quoteMeta(e){return e.split("").map(t=>this.METACHARACTERS.indexOf(t)>=0?`\\${t}`:t).join("")}static charCount(e){return e>J.MAX_BMP?2:1}static stringToUtf8ByteArray(e){if(globalThis.TextEncoder)return Array.from(new TextEncoder().encode(e));{let t=[],n=0;for(let s=0;s<e.length;s++){let i=e.charCodeAt(s);i<128?t[n++]=i:i<2048?(t[n++]=i>>6|192,t[n++]=i&63|128):(i&64512)===55296&&s+1<e.length&&(e.charCodeAt(s+1)&64512)===56320?(i=65536+((i&1023)<<10)+(e.charCodeAt(++s)&1023),t[n++]=i>>18|240,t[n++]=i>>12&63|128,t[n++]=i>>6&63|128,t[n++]=i&63|128):(t[n++]=i>>12|224,t[n++]=i>>6&63|128,t[n++]=i&63|128)}return t}}static utf8ByteArrayToString(e){if(globalThis.TextDecoder)return new TextDecoder("utf-8").decode(new Uint8Array(e));{let t=[],n=0,s=0;for(;n<e.length;){let i=e[n++];if(i<128)t[s++]=String.fromCharCode(i);else if(i>191&&i<224){let o=e[n++];t[s++]=String.fromCharCode((i&31)<<6|o&63)}else if(i>239&&i<365){let o=e[n++],u=e[n++],c=e[n++],l=((i&7)<<18|(o&63)<<12|(u&63)<<6|c&63)-65536;t[s++]=String.fromCharCode(55296+(l>>10)),t[s++]=String.fromCharCode(56320+(l&1023))}else{let o=e[n++],u=e[n++];t[s++]=String.fromCharCode((i&15)<<12|(o&63)<<6|u&63)}}return t.join("")}}}_(re,"METACHARACTERS","\\.+*?()|[]{}^$"),_(re,"EMPTY_BEGIN_LINE",1),_(re,"EMPTY_END_LINE",2),_(re,"EMPTY_BEGIN_TEXT",4),_(re,"EMPTY_END_TEXT",8),_(re,"EMPTY_WORD_BOUNDARY",16),_(re,"EMPTY_NO_WORD_BOUNDARY",32),_(re,"EMPTY_ALL",-1);const K2=(r=[],e=0)=>{const t={};for(let n=0;n<r.length;n++){const s=r[n],i=e+n;t[s]=i,t[i]=s}return Object.freeze(t)},qi=class qi{getEncoding(){throw Error("not implemented")}isUTF8Encoding(){return this.getEncoding()===qi.Encoding.UTF_8}isUTF16Encoding(){return this.getEncoding()===qi.Encoding.UTF_16}};_(qi,"Encoding",K2(["UTF_16","UTF_8"]));let Hn=qi;class od extends Hn{constructor(e=null){super(),this.bytes=e}getEncoding(){return Hn.Encoding.UTF_8}asCharSequence(){return re.utf8ByteArrayToString(this.bytes)}asBytes(){return this.bytes}length(){return this.bytes.length}}class Fm extends Hn{constructor(e=null){super(),this.charSequence=e}getEncoding(){return Hn.Encoding.UTF_16}asCharSequence(){return this.charSequence}asBytes(){return this.charSequence.toString().split("").map(e=>e.codePointAt(0))}length(){return this.charSequence.length}}class Aa{static utf16(e){return new Fm(e)}static utf8(e){return Array.isArray(e)?new od(e):new od(re.stringToUtf8ByteArray(e))}}class tu extends Error{constructor(e){super(e),this.name="RE2JSException"}}class De extends tu{constructor(e,t=null){let n=`error parsing regexp: ${e}`;t&&(n+=`: \`${t}\``),super(n),this.name="RE2JSSyntaxException",this.message=n,this.error=e,this.input=t}getDescription(){return this.error}getPattern(){return this.input}}class Um extends tu{constructor(e){super(e),this.name="RE2JSCompileException"}}class tn extends tu{constructor(e){super(e),this.name="RE2JSGroupException"}}class Bm extends tu{constructor(e){super(e),this.name="RE2JSFlagsException"}}class qm{static quoteReplacement(e){return e.indexOf("\\")<0&&e.indexOf("$")<0?e:e.split("").map(t=>{const n=t.codePointAt(0);return n===V.CODES["\\"]||n===V.CODES.$?`\\${t}`:t}).join("")}constructor(e,t){if(e===null)throw new Error("pattern is null");this.patternInput=e;const n=this.patternInput.re2();this.patternGroupCount=n.numberOfCapturingGroups(),this.groups=[],this.namedGroups=n.namedGroups,t instanceof Hn?this.resetMatcherInput(t):Array.isArray(t)?this.resetMatcherInput(Aa.utf8(t)):this.resetMatcherInput(Aa.utf16(t))}pattern(){return this.patternInput}reset(){return this.matcherInputLength=this.matcherInput.length(),this.appendPos=0,this.hasMatch=!1,this.hasGroups=!1,this.anchorFlag=0,this}resetMatcherInput(e){if(e===null)throw new Error("input is null");return this.matcherInput=e,this.reset(),this}start(e=0){if(typeof e=="string"){const t=this.namedGroups[e];if(!Number.isFinite(t))throw new tn(`group '${e}' not found`);e=t}return this.loadGroup(e),this.groups[2*e]}end(e=0){if(typeof e=="string"){const t=this.namedGroups[e];if(!Number.isFinite(t))throw new tn(`group '${e}' not found`);e=t}return this.loadGroup(e),this.groups[2*e+1]}group(e=0){if(typeof e=="string"){const s=this.namedGroups[e];if(!Number.isFinite(s))throw new tn(`group '${e}' not found`);e=s}const t=this.start(e),n=this.end(e);return t<0&&n<0?null:this.substring(t,n)}groupCount(){return this.patternGroupCount}loadGroup(e){if(e<0||e>this.patternGroupCount)throw new tn(`Group index out of bounds: ${e}`);if(!this.hasMatch)throw new tn("perhaps no match attempted");if(e===0||this.hasGroups)return;let t=this.groups[1]+1;t>this.matcherInputLength&&(t=this.matcherInputLength);const n=this.patternInput.re2().matchMachineInput(this.matcherInput,this.groups[0],t,this.anchorFlag,1+this.patternGroupCount);if(!n[0])throw new tn("inconsistency in matching group data");this.groups=n[1],this.hasGroups=!0}matches(){return this.genMatch(0,z.ANCHOR_BOTH)}lookingAt(){return this.genMatch(0,z.ANCHOR_START)}find(e=null){if(e!==null){if(e<0||e>this.matcherInputLength)throw new tn(`start index out of bounds: ${e}`);return this.reset(),this.genMatch(e,0)}return e=0,this.hasMatch&&(e=this.groups[1],this.groups[0]===this.groups[1]&&e++),this.genMatch(e,z.UNANCHORED)}genMatch(e,t){const n=this.patternInput.re2().matchMachineInput(this.matcherInput,e,this.matcherInputLength,t,1);return n[0]?(this.groups=n[1],this.hasMatch=!0,this.hasGroups=!1,this.anchorFlag=t,!0):!1}substring(e,t){return this.matcherInput.isUTF8Encoding()?re.utf8ByteArrayToString(this.matcherInput.asBytes().slice(e,t)):this.matcherInput.asCharSequence().substring(e,t).toString()}inputLength(){return this.matcherInputLength}appendReplacement(e,t=!1){let n="";const s=this.start(),i=this.end();return this.appendPos<s&&(n+=this.substring(this.appendPos,s)),this.appendPos=i,n+=t?this.appendReplacementInternalPerl(e):this.appendReplacementInternal(e),n}appendReplacementInternal(e){let t="",n=0;const s=e.length;for(let i=0;i<s-1;i++){if(e.codePointAt(i)===V.CODES.get("\\")){n<i&&(t+=e.substring(n,i)),i++,n=i;continue}if(e.codePointAt(i)===V.CODES.get("$")){let o=e.codePointAt(i+1);if(V.CODES.get("0")<=o&&o<=V.CODES.get("9")){let u=o-V.CODES.get("0");for(n<i&&(t+=e.substring(n,i)),i+=2;i<s&&(o=e.codePointAt(i),!(o<V.CODES.get("0")||o>V.CODES.get("9")||u*10+o-V.CODES.get("0")>this.patternGroupCount));i++)u=u*10+o-V.CODES.get("0");if(u>this.patternGroupCount)throw new tn(`n > number of groups: ${u}`);const c=this.group(u);c!==null&&(t+=c),n=i,i--;continue}else if(o===V.CODES.get("{")){n<i&&(t+=e.substring(n,i)),i++;let u=i+1;for(;u<e.length&&e.codePointAt(u)!==V.CODES.get("}")&&e.codePointAt(u)!==V.CODES.get(" ");)u++;if(u===e.length||e.codePointAt(u)!==V.CODES.get("}"))throw new tn("named capture group is missing trailing '}'");const c=e.substring(i+1,u);t+=this.group(c),n=u+1}}}return n<s&&(t+=e.substring(n,s)),t}appendReplacementInternalPerl(e){let t="",n=0;const s=e.length;for(let i=0;i<s-1;i++)if(e.codePointAt(i)===V.CODES.get("$")){let o=e.codePointAt(i+1);if(V.CODES.get("$")===o){n<i&&(t+=e.substring(n,i)),t+="$",i++,n=i+1;continue}else if(V.CODES.get("&")===o){n<i&&(t+=e.substring(n,i));const u=this.group(0);u!==null?t+=u:t+="$&",i++,n=i+1;continue}else if(V.CODES.get("1")<=o&&o<=V.CODES.get("9")){let u=o-V.CODES.get("0");for(n<i&&(t+=e.substring(n,i)),i+=2;i<s&&(o=e.codePointAt(i),!(o<V.CODES.get("0")||o>V.CODES.get("9")||u*10+o-V.CODES.get("0")>this.patternGroupCount));i++)u=u*10+o-V.CODES.get("0");if(u>this.patternGroupCount){t+=`$${u}`,n=i,i--;continue}const c=this.group(u);c!==null&&(t+=c),n=i,i--;continue}else if(o===V.CODES.get("<")){n<i&&(t+=e.substring(n,i)),i++;let u=i+1;for(;u<e.length&&e.codePointAt(u)!==V.CODES.get(">")&&e.codePointAt(u)!==V.CODES.get(" ");)u++;if(u===e.length||e.codePointAt(u)!==V.CODES.get(">")){t+=e.substring(i-1,u+1),n=u+1;continue}const c=e.substring(i+1,u);Object.prototype.hasOwnProperty.call(this.namedGroups,c)?t+=this.group(c):t+=`$<${c}>`,n=u+1}}return n<s&&(t+=e.substring(n,s)),t}appendTail(){return this.substring(this.appendPos,this.matcherInputLength)}replaceAll(e,t=!1){return this.replace(e,!0,t)}replaceFirst(e,t=!1){return this.replace(e,!1,t)}replace(e,t=!0,n=!1){let s="";for(this.reset();this.find()&&(s+=this.appendReplacement(e,n),!!t););return s+=this.appendTail(),s}}class xn{static EOF(){return-8}canCheckPrefix(){return!0}endPos(){return this.end}}class $m extends xn{constructor(e,t=0,n=e.length){super(),this.bytes=e,this.start=t,this.end=n}step(e){if(e+=this.start,e>=this.end)return xn.EOF();let t=this.bytes[e++]&255;return t&128?(t&224)===192?(t=t&31,e>=this.end?xn.EOF():(t=t<<6|this.bytes[e++]&63,t<<3|2)):(t&240)===224?(t=t&15,e+1>=this.end?xn.EOF():(t=t<<6|this.bytes[e++]&63,t=t<<6|this.bytes[e++]&63,t<<3|3)):(t=t&7,e+2>=this.end?xn.EOF():(t=t<<6|this.bytes[e++]&63,t=t<<6|this.bytes[e++]&63,t=t<<6|this.bytes[e++]&63,t<<3|4)):t<<3|1}index(e,t){t+=this.start;const n=this.indexOf(this.bytes,e.prefixUTF8,t);return n<0?n:n-t}context(e){e+=this.start;let t=-1;if(e>this.start&&e<=this.end){let s=e-1;if(t=this.bytes[s--],t>=128){let i=e-4;for(i<this.start&&(i=this.start);s>=i&&(this.bytes[s]&192)===128;)s--;s<this.start&&(s=this.start),t=this.step(s)>>3}}const n=e<this.end?this.step(e)>>3:-1;return re.emptyOpContext(t,n)}indexOf(e,t,n=0){let s=t.length;if(s===0)return-1;let i=e.length;for(let o=n;o<=i-s;o++)for(let u=0;u<s&&e[o+u]===t[u];u++)if(u===s-1)return o;return-1}}class Gm extends xn{constructor(e,t=0,n=e.length){super(),this.charSequence=e,this.start=t,this.end=n}step(e){if(e+=this.start,e<this.end){const t=this.charSequence.codePointAt(e);return t<<3|re.charCount(t)}else return xn.EOF()}index(e,t){t+=this.start;const n=this.charSequence.indexOf(e.prefix,t);return n<0?n:n-t}context(e){e+=this.start;const t=e>0&&e<=this.charSequence.length?this.charSequence.codePointAt(e-1):-1,n=e<this.charSequence.length?this.charSequence.codePointAt(e):-1;return re.emptyOpContext(t,n)}}class Oe{static fromUTF8(e,t=0,n=e.length){return new $m(e,t,n)}static fromUTF16(e,t=0,n=e.length){return new Gm(e,t,n)}}const X=class X{static isPseudoOp(e){return e>=X.Op.LEFT_PAREN}static emptySubs(){return[]}static quoteIfHyphen(e){return e===V.CODES.get("-")?"\\":""}static fromRegexp(e){const t=new X(e.op);return t.flags=e.flags,t.subs=e.subs,t.runes=e.runes,t.cap=e.cap,t.min=e.min,t.max=e.max,t.name=e.name,t.namedGroups=e.namedGroups,t}constructor(e){this.op=e,this.flags=0,this.subs=X.emptySubs(),this.runes=null,this.min=0,this.max=0,this.cap=0,this.name=null,this.namedGroups={}}reinit(){this.flags=0,this.subs=X.emptySubs(),this.runes=null,this.cap=0,this.min=0,this.max=0,this.name=null,this.namedGroups={}}toString(){return this.appendTo()}appendTo(){let e="";switch(this.op){case X.Op.NO_MATCH:e+="[^\\x00-\\x{10FFFF}]";break;case X.Op.EMPTY_MATCH:e+="(?:)";break;case X.Op.STAR:case X.Op.PLUS:case X.Op.QUEST:case X.Op.REPEAT:{const t=this.subs[0];switch(t.op>X.Op.CAPTURE||t.op===X.Op.LITERAL&&t.runes.length>1?e+=`(?:${t.appendTo()})`:e+=t.appendTo(),this.op){case X.Op.STAR:e+="*";break;case X.Op.PLUS:e+="+";break;case X.Op.QUEST:e+="?";break;case X.Op.REPEAT:e+=`{${this.min}`,this.min!==this.max&&(e+=",",this.max>=0&&(e+=this.max)),e+="}";break}this.flags&z.NON_GREEDY&&(e+="?");break}case X.Op.CONCAT:{for(let t of this.subs)t.op===X.Op.ALTERNATE?e+=`(?:${t.appendTo()})`:e+=t.appendTo();break}case X.Op.ALTERNATE:{let t="";for(let n of this.subs)e+=t,t="|",e+=n.appendTo();break}case X.Op.LITERAL:this.flags&z.FOLD_CASE&&(e+="(?i:");for(let t of this.runes)e+=re.escapeRune(t);this.flags&z.FOLD_CASE&&(e+=")");break;case X.Op.ANY_CHAR_NOT_NL:e+="(?-s:.)";break;case X.Op.ANY_CHAR:e+="(?s:.)";break;case X.Op.CAPTURE:this.name===null||this.name.length===0?e+="(":e+=`(?P<${this.name}>`,this.subs[0].op!==X.Op.EMPTY_MATCH&&(e+=this.subs[0].appendTo()),e+=")";break;case X.Op.BEGIN_TEXT:e+="\\A";break;case X.Op.END_TEXT:this.flags&z.WAS_DOLLAR?e+="(?-m:$)":e+="\\z";break;case X.Op.BEGIN_LINE:e+="^";break;case X.Op.END_LINE:e+="$";break;case X.Op.WORD_BOUNDARY:e+="\\b";break;case X.Op.NO_WORD_BOUNDARY:e+="\\B";break;case X.Op.CHAR_CLASS:if(this.runes.length%2!==0){e+="[invalid char class]";break}if(e+="[",this.runes.length===0)e+="^\\x00-\\x{10FFFF}";else if(this.runes[0]===0&&this.runes[this.runes.length-1]===J.MAX_RUNE){e+="^";for(let t=1;t<this.runes.length-1;t+=2){const n=this.runes[t]+1,s=this.runes[t+1]-1;e+=X.quoteIfHyphen(n),e+=re.escapeRune(n),n!==s&&(e+="-",e+=X.quoteIfHyphen(s),e+=re.escapeRune(s))}}else for(let t=0;t<this.runes.length;t+=2){const n=this.runes[t],s=this.runes[t+1];e+=X.quoteIfHyphen(n),e+=re.escapeRune(n),n!==s&&(e+="-",e+=X.quoteIfHyphen(s),e+=re.escapeRune(s))}e+="]";break;default:e+=this.op;break}return e}maxCap(){let e=0;if(this.op===X.Op.CAPTURE&&(e=this.cap),this.subs!==null)for(let t of this.subs){const n=t.maxCap();e<n&&(e=n)}return e}equals(e){if(!(e!==null&&e instanceof X)||this.op!==e.op)return!1;switch(this.op){case X.Op.END_TEXT:{if((this.flags&z.WAS_DOLLAR)!==(e.flags&z.WAS_DOLLAR))return!1;break}case X.Op.LITERAL:case X.Op.CHAR_CLASS:{if(this.runes===null&&e.runes===null)break;if(this.runes===null||e.runes===null||this.runes.length!==e.runes.length)return!1;for(let t=0;t<this.runes.length;t++)if(this.runes[t]!==e.runes[t])return!1;break}case X.Op.ALTERNATE:case X.Op.CONCAT:{if(this.subs.length!==e.subs.length)return!1;for(let t=0;t<this.subs.length;++t)if(!this.subs[t].equals(e.subs[t]))return!1;break}case X.Op.STAR:case X.Op.PLUS:case X.Op.QUEST:{if((this.flags&z.NON_GREEDY)!==(e.flags&z.NON_GREEDY)||!this.subs[0].equals(e.subs[0]))return!1;break}case X.Op.REPEAT:{if((this.flags&z.NON_GREEDY)!==(e.flags&z.NON_GREEDY)||this.min!==e.min||this.max!==e.max||!this.subs[0].equals(e.subs[0]))return!1;break}case X.Op.CAPTURE:{if(this.cap!==e.cap||(this.name===null?e.name!==null:this.name!==e.name)||!this.subs[0].equals(e.subs[0]))return!1;break}}return!0}};_(X,"Op",K2(["NO_MATCH","EMPTY_MATCH","LITERAL","CHAR_CLASS","ANY_CHAR_NOT_NL","ANY_CHAR","BEGIN_LINE","END_LINE","BEGIN_TEXT","END_TEXT","WORD_BOUNDARY","NO_WORD_BOUNDARY","CAPTURE","STAR","PLUS","QUEST","REPEAT","CONCAT","ALTERNATE","LEFT_PAREN","VERTICAL_BAR"]));let k=X;const ye=class ye{static isRuneOp(e){return ye.RUNE<=e&&e<=ye.RUNE_ANY_NOT_NL}static escapeRunes(e){let t='"';for(let n of e)t+=re.escapeRune(n);return t+='"',t}constructor(e){this.op=e,this.out=0,this.arg=0,this.runes=null}matchRune(e){if(this.runes.length===1){const s=this.runes[0];return this.arg&z.FOLD_CASE?J.equalsIgnoreCase(s,e):e===s}for(let s=0;s<this.runes.length&&s<=8;s+=2){if(e<this.runes[s])return!1;if(e<=this.runes[s+1])return!0}let t=0,n=this.runes.length/2|0;for(;t<n;){const s=t+((n-t)/2|0);if(this.runes[2*s]<=e){if(e<=this.runes[2*s+1])return!0;t=s+1}else n=s}return!1}toString(){switch(this.op){case ye.ALT:return`alt -> ${this.out}, ${this.arg}`;case ye.ALT_MATCH:return`altmatch -> ${this.out}, ${this.arg}`;case ye.CAPTURE:return`cap ${this.arg} -> ${this.out}`;case ye.EMPTY_WIDTH:return`empty ${this.arg} -> ${this.out}`;case ye.MATCH:return"match";case ye.FAIL:return"fail";case ye.NOP:return`nop -> ${this.out}`;case ye.RUNE:return this.runes===null?"rune <null>":["rune ",ye.escapeRunes(this.runes),this.arg&z.FOLD_CASE?"/i":""," -> ",this.out].join("");case ye.RUNE1:return`rune1 ${ye.escapeRunes(this.runes)} -> ${this.out}`;case ye.RUNE_ANY:return`any -> ${this.out}`;case ye.RUNE_ANY_NOT_NL:return`anynotnl -> ${this.out}`;default:throw new Error("unhandled case in Inst.toString")}}};_(ye,"ALT",1),_(ye,"ALT_MATCH",2),_(ye,"CAPTURE",3),_(ye,"EMPTY_WIDTH",4),_(ye,"FAIL",5),_(ye,"MATCH",6),_(ye,"NOP",7),_(ye,"RUNE",8),_(ye,"RUNE1",9),_(ye,"RUNE_ANY",10),_(ye,"RUNE_ANY_NOT_NL",11);let ie=ye;class jm{constructor(){this.inst=[],this.start=0,this.numCap=2}getInst(e){return this.inst[e]}numInst(){return this.inst.length}addInst(e){this.inst.push(new ie(e))}skipNop(e){let t=this.inst[e];for(;t.op===ie.NOP||t.op===ie.CAPTURE;)t=this.inst[e],e=t.out;return t}prefix(){let e="",t=this.skipNop(this.start);if(!ie.isRuneOp(t.op)||t.runes.length!==1)return[t.op===ie.MATCH,e];for(;ie.isRuneOp(t.op)&&t.runes.length===1&&!(t.arg&z.FOLD_CASE);)e+=String.fromCodePoint(t.runes[0]),t=this.skipNop(t.out);return[t.op===ie.MATCH,e]}startCond(){let e=0,t=this.start;e:for(;;){const n=this.inst[t];switch(n.op){case ie.EMPTY_WIDTH:e|=n.arg;break;case ie.FAIL:return-1;case ie.CAPTURE:case ie.NOP:break;default:break e}t=n.out}return e}next(e){const t=this.inst[e>>1];return e&1?t.arg:t.out}patch(e,t){for(;e!==0;){const n=this.inst[e>>1];e&1?(e=n.arg,n.arg=t):(e=n.out,n.out=t)}}append(e,t){if(e===0)return t;if(t===0)return e;let n=e;for(;;){const i=this.next(n);if(i===0)break;n=i}const s=this.inst[n>>1];return n&1?s.arg=t:s.out=t,e}toString(){let e="";for(let t=0;t<this.inst.length;t++){const n=e.length;e+=t,t===this.start&&(e+="*"),e+="        ".substring(e.length-n),e+=this.inst[t],e+=`
`}return e}}class Qo{constructor(e=0,t=0,n=!1){this.i=e,this.out=t,this.nullable=n}}class Ri{static ANY_RUNE_NOT_NL(){return[0,V.CODES.get(`
`)-1,V.CODES.get(`
`)+1,J.MAX_RUNE]}static ANY_RUNE(){return[0,J.MAX_RUNE]}static compileRegexp(e){const t=new Ri,n=t.compile(e);return t.prog.patch(n.out,t.newInst(ie.MATCH).i),t.prog.start=n.i,t.prog}constructor(){this.prog=new jm,this.newInst(ie.FAIL)}newInst(e){return this.prog.addInst(e),new Qo(this.prog.numInst()-1,0,!0)}nop(){const e=this.newInst(ie.NOP);return e.out=e.i<<1,e}fail(){return new Qo}cap(e){const t=this.newInst(ie.CAPTURE);return t.out=t.i<<1,this.prog.getInst(t.i).arg=e,this.prog.numCap<e+1&&(this.prog.numCap=e+1),t}cat(e,t){return e.i===0||t.i===0?this.fail():(this.prog.patch(e.out,t.i),new Qo(e.i,t.out,e.nullable&&t.nullable))}alt(e,t){if(e.i===0)return t;if(t.i===0)return e;const n=this.newInst(ie.ALT),s=this.prog.getInst(n.i);return s.out=e.i,s.arg=t.i,n.out=this.prog.append(e.out,t.out),n.nullable=e.nullable||t.nullable,n}loop(e,t){const n=this.newInst(ie.ALT),s=this.prog.getInst(n.i);return t?(s.arg=e.i,n.out=n.i<<1):(s.out=e.i,n.out=n.i<<1|1),this.prog.patch(e.out,n.i),n}quest(e,t){const n=this.newInst(ie.ALT),s=this.prog.getInst(n.i);return t?(s.arg=e.i,n.out=n.i<<1):(s.out=e.i,n.out=n.i<<1|1),n.out=this.prog.append(n.out,e.out),n}star(e,t){return e.nullable?this.quest(this.plus(e,t),t):this.loop(e,t)}plus(e,t){return new Qo(e.i,this.loop(e,t).out,e.nullable)}empty(e){const t=this.newInst(ie.EMPTY_WIDTH);return this.prog.getInst(t.i).arg=e,t.out=t.i<<1,t}rune(e,t){const n=this.newInst(ie.RUNE);n.nullable=!1;const s=this.prog.getInst(n.i);return s.runes=e,t&=z.FOLD_CASE,(e.length!==1||J.simpleFold(e[0])===e[0])&&(t&=-2),s.arg=t,n.out=n.i<<1,!(t&z.FOLD_CASE)&&e.length===1||e.length===2&&e[0]===e[1]?s.op=ie.RUNE1:e.length===2&&e[0]===0&&e[1]===J.MAX_RUNE?s.op=ie.RUNE_ANY:e.length===4&&e[0]===0&&e[1]===V.CODES.get(`
`)-1&&e[2]===V.CODES.get(`
`)+1&&e[3]===J.MAX_RUNE&&(s.op=ie.RUNE_ANY_NOT_NL),n}compile(e){switch(e.op){case k.Op.NO_MATCH:return this.fail();case k.Op.EMPTY_MATCH:return this.nop();case k.Op.LITERAL:if(e.runes.length===0)return this.nop();{let t=null;for(let n of e.runes){const s=this.rune([n],e.flags);t=t===null?s:this.cat(t,s)}return t}case k.Op.CHAR_CLASS:return this.rune(e.runes,e.flags);case k.Op.ANY_CHAR_NOT_NL:return this.rune(Ri.ANY_RUNE_NOT_NL(),0);case k.Op.ANY_CHAR:return this.rune(Ri.ANY_RUNE(),0);case k.Op.BEGIN_LINE:return this.empty(re.EMPTY_BEGIN_LINE);case k.Op.END_LINE:return this.empty(re.EMPTY_END_LINE);case k.Op.BEGIN_TEXT:return this.empty(re.EMPTY_BEGIN_TEXT);case k.Op.END_TEXT:return this.empty(re.EMPTY_END_TEXT);case k.Op.WORD_BOUNDARY:return this.empty(re.EMPTY_WORD_BOUNDARY);case k.Op.NO_WORD_BOUNDARY:return this.empty(re.EMPTY_NO_WORD_BOUNDARY);case k.Op.CAPTURE:{const t=this.cap(e.cap<<1),n=this.compile(e.subs[0]),s=this.cap(e.cap<<1|1);return this.cat(this.cat(t,n),s)}case k.Op.STAR:return this.star(this.compile(e.subs[0]),(e.flags&z.NON_GREEDY)!==0);case k.Op.PLUS:return this.plus(this.compile(e.subs[0]),(e.flags&z.NON_GREEDY)!==0);case k.Op.QUEST:return this.quest(this.compile(e.subs[0]),(e.flags&z.NON_GREEDY)!==0);case k.Op.CONCAT:{if(e.subs.length===0)return this.nop();{let t=null;for(let n of e.subs){const s=this.compile(n);t=t===null?s:this.cat(t,s)}return t}}case k.Op.ALTERNATE:{if(e.subs.length===0)return this.nop();{let t=null;for(let n of e.subs){const s=this.compile(n);t=t===null?s:this.alt(t,s)}return t}}default:throw new Um("regexp: unhandled case in compile")}}}class Pt{static simplify(e){if(e===null)return null;switch(e.op){case k.Op.CAPTURE:case k.Op.CONCAT:case k.Op.ALTERNATE:{let t=e;for(let n=0;n<e.subs.length;n++){const s=e.subs[n],i=Pt.simplify(s);t===e&&i!==s&&(t=k.fromRegexp(e),t.runes=null,t.subs=e.subs.slice(0,e.subs.length)),t!==e&&(t.subs[n]=i)}return t}case k.Op.STAR:case k.Op.PLUS:case k.Op.QUEST:{const t=Pt.simplify(e.subs[0]);return Pt.simplify1(e.op,e.flags,t,e)}case k.Op.REPEAT:{if(e.min===0&&e.max===0)return new k(k.Op.EMPTY_MATCH);const t=Pt.simplify(e.subs[0]);if(e.max===-1){if(e.min===0)return Pt.simplify1(k.Op.STAR,e.flags,t,null);if(e.min===1)return Pt.simplify1(k.Op.PLUS,e.flags,t,null);const s=new k(k.Op.CONCAT),i=[];for(let o=0;o<e.min-1;o++)i.push(t);return i.push(Pt.simplify1(k.Op.PLUS,e.flags,t,null)),s.subs=i.slice(0),s}if(e.min===1&&e.max===1)return t;let n=null;if(e.min>0){n=[];for(let s=0;s<e.min;s++)n.push(t)}if(e.max>e.min){let s=Pt.simplify1(k.Op.QUEST,e.flags,t,null);for(let i=e.min+1;i<e.max;i++){const o=new k(k.Op.CONCAT);o.subs=[t,s],s=Pt.simplify1(k.Op.QUEST,e.flags,o,null)}if(n===null)return s;n.push(s)}if(n!==null){const s=new k(k.Op.CONCAT);return s.subs=n.slice(0),s}return new k(k.Op.NO_MATCH)}}return e}static simplify1(e,t,n,s){return n.op===k.Op.EMPTY_MATCH||e===n.op&&(t&z.NON_GREEDY)===(n.flags&z.NON_GREEDY)?n:(s!==null&&s.op===e&&(s.flags&z.NON_GREEDY)===(t&z.NON_GREEDY)&&n===s.subs[0]||(s=new k(e),s.flags=t,s.subs=[n]),s)}}class he{constructor(e,t){this.sign=e,this.cls=t}}const ad=[48,57],ud=[9,10,12,13,32,32],cd=[48,57,65,90,95,95,97,122],ld=new Map([["\\d",new he(1,ad)],["\\D",new he(-1,ad)],["\\s",new he(1,ud)],["\\S",new he(-1,ud)],["\\w",new he(1,cd)],["\\W",new he(-1,cd)]]),hd=[48,57,65,90,97,122],dd=[65,90,97,122],fd=[0,127],pd=[9,9,32,32],gd=[0,31,127,127],md=[48,57],_d=[33,126],yd=[97,122],Ed=[32,126],Id=[33,47,58,64,91,96,123,126],wd=[9,13,32,32],Td=[65,90],Ad=[48,57,65,90,95,95,97,122],vd=[48,57,65,70,97,102],Rd=new Map([["[:alnum:]",new he(1,hd)],["[:^alnum:]",new he(-1,hd)],["[:alpha:]",new he(1,dd)],["[:^alpha:]",new he(-1,dd)],["[:ascii:]",new he(1,fd)],["[:^ascii:]",new he(-1,fd)],["[:blank:]",new he(1,pd)],["[:^blank:]",new he(-1,pd)],["[:cntrl:]",new he(1,gd)],["[:^cntrl:]",new he(-1,gd)],["[:digit:]",new he(1,md)],["[:^digit:]",new he(-1,md)],["[:graph:]",new he(1,_d)],["[:^graph:]",new he(-1,_d)],["[:lower:]",new he(1,yd)],["[:^lower:]",new he(-1,yd)],["[:print:]",new he(1,Ed)],["[:^print:]",new he(-1,Ed)],["[:punct:]",new he(1,Id)],["[:^punct:]",new he(-1,Id)],["[:space:]",new he(1,wd)],["[:^space:]",new he(-1,wd)],["[:upper:]",new he(1,Td)],["[:^upper:]",new he(-1,Td)],["[:word:]",new he(1,Ad)],["[:^word:]",new he(-1,Ad)],["[:xdigit:]",new he(1,vd)],["[:^xdigit:]",new he(-1,vd)]]);class Ze{static charClassToString(e,t){let n="[";for(let s=0;s<t;s+=2){s>0&&(n+=" ");const i=e[s],o=e[s+1];i===o?n+=`0x${i.toString(16)}`:n+=`0x${i.toString(16)}-0x${o.toString(16)}`}return n+="]",n}static cmp(e,t,n,s){const i=e[t]-n;return i!==0?i:s-e[t+1]}static qsortIntPair(e,t,n){const s=((t+n)/2|0)&-2,i=e[s],o=e[s+1];let u=t,c=n;for(;u<=c;){for(;u<n&&Ze.cmp(e,u,i,o)<0;)u+=2;for(;c>t&&Ze.cmp(e,c,i,o)>0;)c-=2;if(u<=c){if(u!==c){let l=e[u];e[u]=e[c],e[c]=l,l=e[u+1],e[u+1]=e[c+1],e[c+1]=l}u+=2,c-=2}}t<c&&Ze.qsortIntPair(e,t,c),u<n&&Ze.qsortIntPair(e,u,n)}constructor(e=re.emptyInts()){this.r=e,this.len=e.length}toArray(){return this.len===this.r.length?this.r:this.r.slice(0,this.len)}cleanClass(){if(this.len<4)return this;Ze.qsortIntPair(this.r,0,this.len-2);let e=2;for(let t=2;t<this.len;t+=2){const n=this.r[t],s=this.r[t+1];if(n<=this.r[e-1]+1){s>this.r[e-1]&&(this.r[e-1]=s);continue}this.r[e]=n,this.r[e+1]=s,e+=2}return this.len=e,this}appendLiteral(e,t){return t&z.FOLD_CASE?this.appendFoldedRange(e,e):this.appendRange(e,e)}appendRange(e,t){if(this.len>0){for(let n=2;n<=4;n+=2)if(this.len>=n){const s=this.r[this.len-n],i=this.r[this.len-n+1];if(e<=i+1&&s<=t+1)return e<s&&(this.r[this.len-n]=e),t>i&&(this.r[this.len-n+1]=t),this}}return this.r[this.len++]=e,this.r[this.len++]=t,this}appendFoldedRange(e,t){if(e<=J.MIN_FOLD&&t>=J.MAX_FOLD)return this.appendRange(e,t);if(t<J.MIN_FOLD||e>J.MAX_FOLD)return this.appendRange(e,t);e<J.MIN_FOLD&&(this.appendRange(e,J.MIN_FOLD-1),e=J.MIN_FOLD),t>J.MAX_FOLD&&(this.appendRange(J.MAX_FOLD+1,t),t=J.MAX_FOLD);for(let n=e;n<=t;n++){this.appendRange(n,n);for(let s=J.simpleFold(n);s!==n;s=J.simpleFold(s))this.appendRange(s,s)}return this}appendClass(e){for(let t=0;t<e.length;t+=2)this.appendRange(e[t],e[t+1]);return this}appendFoldedClass(e){for(let t=0;t<e.length;t+=2)this.appendFoldedRange(e[t],e[t+1]);return this}appendNegatedClass(e){let t=0;for(let n=0;n<e.length;n+=2){const s=e[n],i=e[n+1];t<=s-1&&this.appendRange(t,s-1),t=i+1}return t<=J.MAX_RUNE&&this.appendRange(t,J.MAX_RUNE),this}appendTable(e){for(let t of e){const n=t[0],s=t[1],i=t[2];if(i===1){this.appendRange(n,s);continue}for(let o=n;o<=s;o+=i)this.appendRange(o,o)}return this}appendNegatedTable(e){let t=0;for(let n of e){const s=n[0],i=n[1],o=n[2];if(o===1){t<=s-1&&this.appendRange(t,s-1),t=i+1;continue}for(let u=s;u<=i;u+=o)t<=u-1&&this.appendRange(t,u-1),t=u+1}return t<=J.MAX_RUNE&&this.appendRange(t,J.MAX_RUNE),this}appendTableWithSign(e,t){return t<0?this.appendNegatedTable(e):this.appendTable(e)}negateClass(){let e=0,t=0;for(let n=0;n<this.len;n+=2){const s=this.r[n],i=this.r[n+1];e<=s-1&&(this.r[t]=e,this.r[t+1]=s-1,t+=2),e=i+1}return this.len=t,e<=J.MAX_RUNE&&(this.r[this.len++]=e,this.r[this.len++]=J.MAX_RUNE),this}appendClassWithSign(e,t){return t<0?this.appendNegatedClass(e):this.appendClass(e)}appendGroup(e,t){let n=e.cls;return t&&(n=new Ze().appendFoldedClass(n).cleanClass().toArray()),this.appendClassWithSign(n,e.sign)}toString(){return Ze.charClassToString(this.r,this.len)}}class Si{static of(e,t){return new Si(e,t)}constructor(e,t){this.first=e,this.second=t}}class zm{constructor(e){this.str=e,this.position=0}pos(){return this.position}rewindTo(e){this.position=e}more(){return this.position<this.str.length}peek(){return this.str.codePointAt(this.position)}skip(e){this.position+=e}skipString(e){this.position+=e.length}pop(){const e=this.str.codePointAt(this.position);return this.position+=re.charCount(e),e}lookingAt(e){return this.rest().startsWith(e)}rest(){return this.str.substring(this.position)}from(e){return this.str.substring(e,this.position)}toString(){return this.rest()}}const W=class W{static ANY_TABLE(){return[[0,J.MAX_RUNE,1]]}static unicodeTable(e){return e==="Any"?Si.of(W.ANY_TABLE(),W.ANY_TABLE()):Je.CATEGORIES.has(e)?Si.of(Je.CATEGORIES.get(e),Je.FOLD_CATEGORIES.get(e)):Je.SCRIPTS.has(e)?Si.of(Je.SCRIPTS.get(e),Je.FOLD_SCRIPT.get(e)):null}static minFoldRune(e){if(e<J.MIN_FOLD||e>J.MAX_FOLD)return e;let t=e;const n=e;for(e=J.simpleFold(e);e!==n;e=J.simpleFold(e))t>e&&(t=e);return t}static leadingRegexp(e){if(e.op===k.Op.EMPTY_MATCH)return null;if(e.op===k.Op.CONCAT&&e.subs.length>0){const t=e.subs[0];return t.op===k.Op.EMPTY_MATCH?null:t}return e}static literalRegexp(e,t){const n=new k(k.Op.LITERAL);return n.flags=t,n.runes=re.stringToRunes(e),n}static parse(e,t){return new W(e,t).parseInternal()}static parseRepeat(e){const t=e.pos();if(!e.more()||!e.lookingAt("{"))return-1;e.skip(1);const n=W.parseInt(e);if(n===-1||!e.more())return-1;let s;if(!e.lookingAt(","))s=n;else{if(e.skip(1),!e.more())return-1;if(e.lookingAt("}"))s=-1;else if((s=W.parseInt(e))===-1)return-1}if(!e.more()||!e.lookingAt("}"))return-1;if(e.skip(1),n<0||n>1e3||s===-2||s>1e3||s>=0&&n>s)throw new De(W.ERR_INVALID_REPEAT_SIZE,e.from(t));return n<<16|s&J.MAX_BMP}static isValidCaptureName(e){if(e.length===0)return!1;for(let t=0;t<e.length;t++){const n=e.codePointAt(t);if(n!==V.CODES.get("_")&&!re.isalnum(n))return!1}return!0}static parseInt(e){const t=e.pos();for(;e.more()&&e.peek()>=V.CODES.get("0")&&e.peek()<=V.CODES.get("9");)e.skip(1);const n=e.from(t);return n.length===0||n.length>1&&n.codePointAt(0)===V.CODES.get("0")?-1:n.length>8?-2:parseFloat(n,10)}static isCharClass(e){return e.op===k.Op.LITERAL&&e.runes.length===1||e.op===k.Op.CHAR_CLASS||e.op===k.Op.ANY_CHAR_NOT_NL||e.op===k.Op.ANY_CHAR}static matchRune(e,t){switch(e.op){case k.Op.LITERAL:return e.runes.length===1&&e.runes[0]===t;case k.Op.CHAR_CLASS:for(let n=0;n<e.runes.length;n+=2)if(e.runes[n]<=t&&t<=e.runes[n+1])return!0;return!1;case k.Op.ANY_CHAR_NOT_NL:return t!==V.CODES.get(`
`);case k.Op.ANY_CHAR:return!0}return!1}static mergeCharClass(e,t){switch(e.op){case k.Op.ANY_CHAR:break;case k.Op.ANY_CHAR_NOT_NL:W.matchRune(t,V.CODES.get(`
`))&&(e.op=k.Op.ANY_CHAR);break;case k.Op.CHAR_CLASS:t.op===k.Op.LITERAL?e.runes=new Ze(e.runes).appendLiteral(t.runes[0],t.flags).toArray():e.runes=new Ze(e.runes).appendClass(t.runes).toArray();break;case k.Op.LITERAL:if(t.runes[0]===e.runes[0]&&t.flags===e.flags)break;e.op=k.Op.CHAR_CLASS,e.runes=new Ze().appendLiteral(e.runes[0],e.flags).appendLiteral(t.runes[0],t.flags).toArray();break}}static parseEscape(e){const t=e.pos();if(e.skip(1),!e.more())throw new De(W.ERR_TRAILING_BACKSLASH);let n=e.pop();e:switch(n){case V.CODES.get("1"):case V.CODES.get("2"):case V.CODES.get("3"):case V.CODES.get("4"):case V.CODES.get("5"):case V.CODES.get("6"):case V.CODES.get("7"):if(!e.more()||e.peek()<V.CODES.get("0")||e.peek()>V.CODES.get("7"))break;case V.CODES.get("0"):{let s=n-V.CODES.get("0");for(let i=1;i<3&&!(!e.more()||e.peek()<V.CODES.get("0")||e.peek()>V.CODES.get("7"));i++)s=s*8+e.peek()-V.CODES.get("0"),e.skip(1);return s}case V.CODES.get("x"):{if(!e.more())break;if(n=e.pop(),n===V.CODES.get("{")){let o=0,u=0;for(;;){if(!e.more())break e;if(n=e.pop(),n===V.CODES.get("}"))break;const c=re.unhex(n);if(c<0||(u=u*16+c,u>J.MAX_RUNE))break e;o++}if(o===0)break e;return u}const s=re.unhex(n);if(!e.more())break;n=e.pop();const i=re.unhex(n);if(s<0||i<0)break;return s*16+i}case V.CODES.get("a"):return V.CODES.get("\x07");case V.CODES.get("f"):return V.CODES.get("\f");case V.CODES.get("n"):return V.CODES.get(`
`);case V.CODES.get("r"):return V.CODES.get("\r");case V.CODES.get("t"):return V.CODES.get("	");case V.CODES.get("v"):return V.CODES.get("\v");default:if(!re.isalnum(n))return n;break}throw new De(W.ERR_INVALID_ESCAPE,e.from(t))}static parseClassChar(e,t){if(!e.more())throw new De(W.ERR_MISSING_BRACKET,e.from(t));return e.lookingAt("\\")?W.parseEscape(e):e.pop()}static concatRunes(e,t){return[...e,...t]}constructor(e,t=0){this.wholeRegexp=e,this.flags=t,this.numCap=0,this.namedGroups={},this.stack=[],this.free=null}newRegexp(e){let t=this.free;return t!==null&&t.subs!==null&&t.subs.length>0?(this.free=t.subs[0],t.reinit(),t.op=e):t=new k(e),t}reuse(e){e.subs!==null&&e.subs.length>0&&(e.subs[0]=this.free),this.free=e}pop(){return this.stack.pop()}popToPseudo(){const e=this.stack.length;let t=e;for(;t>0&&!k.isPseudoOp(this.stack[t-1].op);)t--;const n=this.stack.slice(t,e);return this.stack=this.stack.slice(0,t),n}push(e){if(e.op===k.Op.CHAR_CLASS&&e.runes.length===2&&e.runes[0]===e.runes[1]){if(this.maybeConcat(e.runes[0],this.flags&-2))return null;e.op=k.Op.LITERAL,e.runes=[e.runes[0]],e.flags=this.flags&-2}else if(e.op===k.Op.CHAR_CLASS&&e.runes.length===4&&e.runes[0]===e.runes[1]&&e.runes[2]===e.runes[3]&&J.simpleFold(e.runes[0])===e.runes[2]&&J.simpleFold(e.runes[2])===e.runes[0]||e.op===k.Op.CHAR_CLASS&&e.runes.length===2&&e.runes[0]+1===e.runes[1]&&J.simpleFold(e.runes[0])===e.runes[1]&&J.simpleFold(e.runes[1])===e.runes[0]){if(this.maybeConcat(e.runes[0],this.flags|z.FOLD_CASE))return null;e.op=k.Op.LITERAL,e.runes=[e.runes[0]],e.flags=this.flags|z.FOLD_CASE}else this.maybeConcat(-1,0);return this.stack.push(e),e}maybeConcat(e,t){const n=this.stack.length;if(n<2)return!1;const s=this.stack[n-1],i=this.stack[n-2];return s.op!==k.Op.LITERAL||i.op!==k.Op.LITERAL||(s.flags&z.FOLD_CASE)!==(i.flags&z.FOLD_CASE)?!1:(i.runes=W.concatRunes(i.runes,s.runes),e>=0?(s.runes=[e],s.flags=t,!0):(this.pop(),this.reuse(s),!1))}newLiteral(e,t){const n=this.newRegexp(k.Op.LITERAL);return n.flags=t,t&z.FOLD_CASE&&(e=W.minFoldRune(e)),n.runes=[e],n}literal(e){this.push(this.newLiteral(e,this.flags))}op(e){const t=this.newRegexp(e);return t.flags=this.flags,this.push(t)}repeat(e,t,n,s,i,o){let u=this.flags;if(u&z.PERL_X&&(i.more()&&i.lookingAt("?")&&(i.skip(1),u^=z.NON_GREEDY),o!==-1))throw new De(W.ERR_INVALID_REPEAT_OP,i.from(o));const c=this.stack.length;if(c===0)throw new De(W.ERR_MISSING_REPEAT_ARGUMENT,i.from(s));const l=this.stack[c-1];if(k.isPseudoOp(l.op))throw new De(W.ERR_MISSING_REPEAT_ARGUMENT,i.from(s));const d=this.newRegexp(e);d.min=t,d.max=n,d.flags=u,d.subs=[l],this.stack[c-1]=d}concat(){this.maybeConcat(-1,0);const e=this.popToPseudo();return e.length===0?this.push(this.newRegexp(k.Op.EMPTY_MATCH)):this.push(this.collapse(e,k.Op.CONCAT))}alternate(){const e=this.popToPseudo();return e.length>0&&this.cleanAlt(e[e.length-1]),e.length===0?this.push(this.newRegexp(k.Op.NO_MATCH)):this.push(this.collapse(e,k.Op.ALTERNATE))}cleanAlt(e){e.op===k.Op.CHAR_CLASS&&(e.runes=new Ze(e.runes).cleanClass().toArray(),e.runes.length===2&&e.runes[0]===0&&e.runes[1]===J.MAX_RUNE?(e.runes=null,e.op=k.Op.ANY_CHAR):e.runes.length===4&&e.runes[0]===0&&e.runes[1]===V.CODES.get(`
`)-1&&e.runes[2]===V.CODES.get(`
`)+1&&e.runes[3]===J.MAX_RUNE&&(e.runes=null,e.op=k.Op.ANY_CHAR_NOT_NL))}collapse(e,t){if(e.length===1)return e[0];let n=0;for(let u of e)n+=u.op===t?u.subs.length:1;let s=new Array(n).fill(null),i=0;for(let u of e)u.op===t?(s.splice(i,u.subs.length,...u.subs),i+=u.subs.length,this.reuse(u)):s[i++]=u;let o=this.newRegexp(t);if(o.subs=s,t===k.Op.ALTERNATE&&(o.subs=this.factor(o.subs),o.subs.length===1)){const u=o;o=o.subs[0],this.reuse(u)}return o}factor(e){if(e.length<2)return e;let t=0,n=e.length,s=0,i=null,o=0,u=0,c=0;for(let d=0;d<=n;d++){let g=null,y=0,R=0;if(d<n){let C=e[t+d];if(C.op===k.Op.CONCAT&&C.subs.length>0&&(C=C.subs[0]),C.op===k.Op.LITERAL&&(g=C.runes,y=C.runes.length,R=C.flags&z.FOLD_CASE),R===u){let M=0;for(;M<o&&M<y&&i[M]===g[M];)M++;if(M>0){o=M;continue}}}if(d!==c)if(d===c+1)e[s++]=e[t+c];else{const C=this.newRegexp(k.Op.LITERAL);C.flags=u,C.runes=i.slice(0,o);for(let Q=c;Q<d;Q++)e[t+Q]=this.removeLeadingString(e[t+Q],o);const M=this.collapse(e.slice(t+c,t+d),k.Op.ALTERNATE),q=this.newRegexp(k.Op.CONCAT);q.subs=[C,M],e[s++]=q}c=d,i=g,o=y,u=R}n=s,t=0,c=0,s=0;let l=null;for(let d=0;d<=n;d++){let g=null;if(!(d<n&&(g=W.leadingRegexp(e[t+d]),l!==null&&l.equals(g)&&(W.isCharClass(l)||l.op===k.Op.REPEAT&&l.min===l.max&&W.isCharClass(l.subs[0]))))){if(d!==c)if(d===c+1)e[s++]=e[t+c];else{const y=l;for(let M=c;M<d;M++){const q=M!==c;e[t+M]=this.removeLeadingRegexp(e[t+M],q)}const R=this.collapse(e.slice(t+c,t+d),k.Op.ALTERNATE),C=this.newRegexp(k.Op.CONCAT);C.subs=[y,R],e[s++]=C}c=d,l=g}}n=s,t=0,c=0,s=0;for(let d=0;d<=n;d++)if(!(d<n&&W.isCharClass(e[t+d]))){if(d!==c)if(d===c+1)e[s++]=e[t+c];else{let g=c;for(let R=c+1;R<d;R++){const C=e[t+g],M=e[t+R];(C.op<M.op||C.op===M.op&&(C.runes!==null?C.runes.length:0)<(M.runes!==null?M.runes.length:0))&&(g=R)}const y=e[t+c];e[t+c]=e[t+g],e[t+g]=y;for(let R=c+1;R<d;R++)W.mergeCharClass(e[t+c],e[t+R]),this.reuse(e[t+R]);this.cleanAlt(e[t+c]),e[s++]=e[t+c]}d<n&&(e[s++]=e[t+d]),c=d+1}n=s,t=0,c=0,s=0;for(let d=0;d<n;++d)d+1<n&&e[t+d].op===k.Op.EMPTY_MATCH&&e[t+d+1].op===k.Op.EMPTY_MATCH||(e[s++]=e[t+d]);return n=s,t=0,e.slice(t,n)}removeLeadingString(e,t){if(e.op===k.Op.CONCAT&&e.subs.length>0){const n=this.removeLeadingString(e.subs[0],t);if(e.subs[0]=n,n.op===k.Op.EMPTY_MATCH)switch(this.reuse(n),e.subs.length){case 0:case 1:e.op=k.Op.EMPTY_MATCH,e.subs=null;break;case 2:{const s=e;e=e.subs[1],this.reuse(s);break}default:e.subs=e.subs.slice(1,e.subs.length);break}return e}return e.op===k.Op.LITERAL&&(e.runes=e.runes.slice(t,e.runes.length),e.runes.length===0&&(e.op=k.Op.EMPTY_MATCH)),e}removeLeadingRegexp(e,t){if(e.op===k.Op.CONCAT&&e.subs.length>0){switch(t&&this.reuse(e.subs[0]),e.subs=e.subs.slice(1,e.subs.length),e.subs.length){case 0:{e.op=k.Op.EMPTY_MATCH,e.subs=k.emptySubs();break}case 1:{const n=e;e=e.subs[0],this.reuse(n);break}}return e}return t&&this.reuse(e),this.newRegexp(k.Op.EMPTY_MATCH)}parseInternal(){if(this.flags&z.LITERAL)return W.literalRegexp(this.wholeRegexp,this.flags);let e=-1,t=-1,n=-1;const s=new zm(this.wholeRegexp);for(;s.more();){let o=-1;e:switch(s.peek()){case V.CODES.get("("):if(this.flags&z.PERL_X&&s.lookingAt("(?")){this.parsePerlFlags(s);break}this.op(k.Op.LEFT_PAREN).cap=++this.numCap,s.skip(1);break;case V.CODES.get("|"):this.parseVerticalBar(),s.skip(1);break;case V.CODES.get(")"):this.parseRightParen(),s.skip(1);break;case V.CODES.get("^"):this.flags&z.ONE_LINE?this.op(k.Op.BEGIN_TEXT):this.op(k.Op.BEGIN_LINE),s.skip(1);break;case V.CODES.get("$"):this.flags&z.ONE_LINE?this.op(k.Op.END_TEXT).flags|=z.WAS_DOLLAR:this.op(k.Op.END_LINE),s.skip(1);break;case V.CODES.get("."):this.flags&z.DOT_NL?this.op(k.Op.ANY_CHAR):this.op(k.Op.ANY_CHAR_NOT_NL),s.skip(1);break;case V.CODES.get("["):this.parseClass(s);break;case V.CODES.get("*"):case V.CODES.get("+"):case V.CODES.get("?"):{o=s.pos();let u=null;switch(s.pop()){case V.CODES.get("*"):u=k.Op.STAR;break;case V.CODES.get("+"):u=k.Op.PLUS;break;case V.CODES.get("?"):u=k.Op.QUEST;break}this.repeat(u,t,n,o,s,e);break}case V.CODES.get("{"):{o=s.pos();const u=W.parseRepeat(s);if(u<0){s.rewindTo(o),this.literal(s.pop());break}t=u>>16,n=(u&J.MAX_BMP)<<16>>16,this.repeat(k.Op.REPEAT,t,n,o,s,e);break}case V.CODES.get("\\"):{const u=s.pos();if(s.skip(1),this.flags&z.PERL_X&&s.more())switch(s.pop()){case V.CODES.get("A"):this.op(k.Op.BEGIN_TEXT);break e;case V.CODES.get("b"):this.op(k.Op.WORD_BOUNDARY);break e;case V.CODES.get("B"):this.op(k.Op.NO_WORD_BOUNDARY);break e;case V.CODES.get("C"):throw new De(W.ERR_INVALID_ESCAPE,"\\C");case V.CODES.get("Q"):{let g=s.rest();const y=g.indexOf("\\E");y>=0&&(g=g.substring(0,y)),s.skipString(g),s.skipString("\\E");let R=0;for(;R<g.length;){const C=g.codePointAt(R);this.literal(C),R+=re.charCount(C)}break e}case V.CODES.get("z"):this.op(k.Op.END_TEXT);break e;default:s.rewindTo(u);break}const c=this.newRegexp(k.Op.CHAR_CLASS);if(c.flags=this.flags,s.lookingAt("\\p")||s.lookingAt("\\P")){const d=new Ze;if(this.parseUnicodeClass(s,d)){c.runes=d.toArray(),this.push(c);break e}}const l=new Ze;if(this.parsePerlClassEscape(s,l)){c.runes=l.toArray(),this.push(c);break e}s.rewindTo(u),this.reuse(c),this.literal(W.parseEscape(s));break}default:this.literal(s.pop());break}e=o}if(this.concat(),this.swapVerticalBar()&&this.pop(),this.alternate(),this.stack.length!==1)throw new De(W.ERR_MISSING_PAREN,this.wholeRegexp);return this.stack[0].namedGroups=this.namedGroups,this.stack[0]}parsePerlFlags(e){const t=e.pos(),n=e.rest();if(n.startsWith("(?P<")||n.startsWith("(?<")){const u=n.charAt(2)==="P"?4:3,c=n.indexOf(">");if(c<0)throw new De(W.ERR_INVALID_NAMED_CAPTURE,n);const l=n.substring(u,c);if(e.skipString(l),e.skip(u+1),!W.isValidCaptureName(l))throw new De(W.ERR_INVALID_NAMED_CAPTURE,n.substring(0,c+1));const d=this.op(k.Op.LEFT_PAREN);if(d.cap=++this.numCap,this.namedGroups[l])throw new De(W.ERR_DUPLICATE_NAMED_CAPTURE,l);this.namedGroups[l]=this.numCap,d.name=l;return}e.skip(2);let s=this.flags,i=1,o=!1;e:for(;e.more();){const u=e.pop();switch(u){case V.CODES.get("i"):s|=z.FOLD_CASE,o=!0;break;case V.CODES.get("m"):s&=-17,o=!0;break;case V.CODES.get("s"):s|=z.DOT_NL,o=!0;break;case V.CODES.get("U"):s|=z.NON_GREEDY,o=!0;break;case V.CODES.get("-"):if(i<0)break e;i=-1,s=~s,o=!1;break;case V.CODES.get(":"):case V.CODES.get(")"):if(i<0){if(!o)break e;s=~s}u===V.CODES.get(":")&&this.op(k.Op.LEFT_PAREN),this.flags=s;return;default:break e}}throw new De(W.ERR_INVALID_PERL_OP,e.from(t))}parseVerticalBar(){this.concat(),this.swapVerticalBar()||this.op(k.Op.VERTICAL_BAR)}swapVerticalBar(){const e=this.stack.length;if(e>=3&&this.stack[e-2].op===k.Op.VERTICAL_BAR&&W.isCharClass(this.stack[e-1])&&W.isCharClass(this.stack[e-3])){let t=this.stack[e-1],n=this.stack[e-3];if(t.op>n.op){const s=n;n=t,t=s,this.stack[e-3]=n}return W.mergeCharClass(n,t),this.reuse(t),this.pop(),!0}if(e>=2){const t=this.stack[e-1],n=this.stack[e-2];if(n.op===k.Op.VERTICAL_BAR)return e>=3&&this.cleanAlt(this.stack[e-3]),this.stack[e-2]=t,this.stack[e-1]=n,!0}return!1}parseRightParen(){if(this.concat(),this.swapVerticalBar()&&this.pop(),this.alternate(),this.stack.length<2)throw new De(W.ERR_INTERNAL_ERROR,"stack underflow");const t=this.pop(),n=this.pop();if(n.op!==k.Op.LEFT_PAREN)throw new De(W.ERR_MISSING_PAREN,this.wholeRegexp);this.flags=n.flags,n.cap===0?this.push(t):(n.op=k.Op.CAPTURE,n.subs=[t],this.push(n))}parsePerlClassEscape(e,t){const n=e.pos();if(!(this.flags&z.PERL_X)||!e.more()||e.pop()!==V.CODES.get("\\")||!e.more())return!1;e.pop();const s=e.from(n),i=ld.has(s)?ld.get(s):null;return i===null?!1:(t.appendGroup(i,(this.flags&z.FOLD_CASE)!==0),!0)}parseNamedClass(e,t){const n=e.rest(),s=n.indexOf(":]");if(s<0)return!1;const i=n.substring(0,s+2);e.skipString(i);const o=Rd.has(i)?Rd.get(i):null;if(o===null)throw new De(W.ERR_INVALID_CHAR_RANGE,i);return t.appendGroup(o,(this.flags&z.FOLD_CASE)!==0),!0}parseUnicodeClass(e,t){const n=e.pos();if(!(this.flags&z.UNICODE_GROUPS)||!e.lookingAt("\\p")&&!e.lookingAt("\\P"))return!1;e.skip(1);let s=1,i=e.pop();if(i===V.CODES.get("P")&&(s=-1),!e.more())throw e.rewindTo(n),new De(W.ERR_INVALID_CHAR_RANGE,e.rest());i=e.pop();let o;if(i!==V.CODES.get("{"))o=re.runeToString(i);else{const d=e.rest(),g=d.indexOf("}");if(g<0)throw e.rewindTo(n),new De(W.ERR_INVALID_CHAR_RANGE,e.rest());o=d.substring(0,g),e.skipString(o),e.skip(1)}o.length!==0&&o.codePointAt(0)===V.CODES.get("^")&&(s=0-s,o=o.substring(1));const u=W.unicodeTable(o);if(u===null)throw new De(W.ERR_INVALID_CHAR_RANGE,e.from(n));const c=u.first,l=u.second;if(!(this.flags&z.FOLD_CASE)||l===null)t.appendTableWithSign(c,s);else{const d=new Ze().appendTable(c).appendTable(l).cleanClass().toArray();t.appendClassWithSign(d,s)}return!0}parseClass(e){const t=e.pos();e.skip(1);const n=this.newRegexp(k.Op.CHAR_CLASS);n.flags=this.flags;const s=new Ze;let i=1;e.more()&&e.lookingAt("^")&&(i=-1,e.skip(1),this.flags&z.CLASS_NL||s.appendRange(V.CODES.get(`
`),V.CODES.get(`
`)));let o=!0;for(;!e.more()||e.peek()!==V.CODES.get("]")||o;){if(e.more()&&e.lookingAt("-")&&!(this.flags&z.PERL_X)&&!o){const d=e.rest();if(d==="-"||!d.startsWith("-]"))throw e.rewindTo(t),new De(W.ERR_INVALID_CHAR_RANGE,e.rest())}o=!1;const u=e.pos();if(e.lookingAt("[:")){if(this.parseNamedClass(e,s))continue;e.rewindTo(u)}if(this.parseUnicodeClass(e,s)||this.parsePerlClassEscape(e,s))continue;e.rewindTo(u);const c=W.parseClassChar(e,t);let l=c;if(e.more()&&e.lookingAt("-")){if(e.skip(1),e.more()&&e.lookingAt("]"))e.skip(-1);else if(l=W.parseClassChar(e,t),l<c)throw new De(W.ERR_INVALID_CHAR_RANGE,e.from(u))}this.flags&z.FOLD_CASE?s.appendFoldedRange(c,l):s.appendRange(c,l)}e.skip(1),s.cleanClass(),i<0&&s.negateClass(),n.runes=s.toArray(),this.push(n)}};_(W,"ERR_INTERNAL_ERROR","regexp/syntax: internal error"),_(W,"ERR_INVALID_CHAR_RANGE","invalid character class range"),_(W,"ERR_INVALID_ESCAPE","invalid escape sequence"),_(W,"ERR_INVALID_NAMED_CAPTURE","invalid named capture"),_(W,"ERR_INVALID_PERL_OP","invalid or unsupported Perl syntax"),_(W,"ERR_INVALID_REPEAT_OP","invalid nested repetition operator"),_(W,"ERR_INVALID_REPEAT_SIZE","invalid repeat count"),_(W,"ERR_MISSING_BRACKET","missing closing ]"),_(W,"ERR_MISSING_PAREN","missing closing )"),_(W,"ERR_MISSING_REPEAT_ARGUMENT","missing argument to repetition operator"),_(W,"ERR_TRAILING_BACKSLASH","trailing backslash at end of expression"),_(W,"ERR_DUPLICATE_NAMED_CAPTURE","duplicate capture group name");let Nc=W;class Hm{constructor(){this.inst=null,this.cap=[]}}class Sd{constructor(){this.sparse=[],this.densePcs=[],this.denseThreads=[],this.size=0}contains(e){const t=this.sparse[e];return t<this.size&&this.densePcs[t]===e}isEmpty(){return this.size===0}add(e){const t=this.size++;return this.sparse[e]=t,this.denseThreads[t]=null,this.densePcs[t]=e,t}clear(){this.sparse=[],this.densePcs=[],this.denseThreads=[],this.size=0}toString(){let e="{";for(let t=0;t<this.size;t++)t!==0&&(e+=", "),e+=this.densePcs[t];return e+="}",e}}class os{static fromRE2(e){const t=new os;return t.prog=e.prog,t.re2=e,t.q0=new Sd(t.prog.numInst()),t.q1=new Sd(t.prog.numInst()),t.pool=[],t.poolSize=0,t.matched=!1,t.matchcap=Array(t.prog.numCap<2?2:t.prog.numCap).fill(0),t.ncap=0,t}static fromMachine(e){const t=new os;return t.re2=e.re2,t.prog=e.prog,t.q0=e.q0,t.q1=e.q1,t.pool=e.pool,t.poolSize=e.poolSize,t.matched=e.matched,t.matchcap=e.matchcap,t.ncap=e.ncap,t}init(e){this.ncap=e,e>this.matchcap.length?this.initNewCap(e):this.resetCap(e)}resetCap(e){for(let t=0;t<this.poolSize;t++){const n=this.pool[t];n.cap=Array(e).fill(0)}}initNewCap(e){for(let t=0;t<this.poolSize;t++){const n=this.pool[t];n.cap=Array(e).fill(0)}this.matchcap=Array(e).fill(0)}submatches(){return this.ncap===0?re.emptyInts():this.matchcap.slice(0,this.ncap)}alloc(e){let t;return this.poolSize>0?(this.poolSize--,t=this.pool[this.poolSize]):t=new Hm,t.inst=e,t}freeQueue(e,t=0){const n=e.size-t,s=this.poolSize+n;this.pool.length<s&&(this.pool=this.pool.slice(0,Math.max(this.pool.length*2,s)));for(let i=t;i<e.size;i++){const o=e.denseThreads[i];o!==null&&(this.pool[this.poolSize]=o,this.poolSize++)}e.clear()}freeThread(e){this.pool.length<=this.poolSize&&(this.pool=this.pool.slice(0,this.pool.length*2)),this.pool[this.poolSize]=e,this.poolSize++}match(e,t,n){const s=this.re2.cond;if(s===re.EMPTY_ALL||(n===z.ANCHOR_START||n===z.ANCHOR_BOTH)&&t!==0)return!1;this.matched=!1,this.matchcap=Array(this.prog.numCap).fill(-1);let i=this.q0,o=this.q1,u=e.step(t),c=u>>3,l=u&7,d=-1,g=0;u!==xn.EOF()&&(u=e.step(t+l),d=u>>3,g=u&7);let y;for(t===0?y=re.emptyOpContext(-1,c):y=e.context(t);;){if(i.isEmpty()){if(s&re.EMPTY_BEGIN_TEXT&&t!==0||this.matched)break;if(this.re2.prefix.length!==0&&d!==this.re2.prefixRune&&e.canCheckPrefix()){const M=e.index(this.re2,t);if(M<0)break;t+=M,u=e.step(t),c=u>>3,l=u&7,u=e.step(t+l),d=u>>3,g=u&7}}!this.matched&&(t===0||n===z.UNANCHORED)&&(this.ncap>0&&(this.matchcap[0]=t),this.add(i,this.prog.start,t,this.matchcap,y,null));const R=t+l;if(y=e.context(R),this.step(i,o,t,R,c,y,n,t===e.endPos()),l===0||this.ncap===0&&this.matched)break;t+=l,c=d,l=g,c!==-1&&(u=e.step(t+l),d=u>>3,g=u&7);const C=i;i=o,o=C}return this.freeQueue(o),this.matched}step(e,t,n,s,i,o,u,c){const l=this.re2.longest;for(let d=0;d<e.size;d++){let g=e.denseThreads[d];if(g===null)continue;if(l&&this.matched&&this.ncap>0&&this.matchcap[0]<g.cap[0]){this.freeThread(g);continue}const y=g.inst;let R=!1;switch(y.op){case ie.MATCH:if(u===z.ANCHOR_BOTH&&!c)break;this.ncap>0&&(!l||!this.matched||this.matchcap[1]<n)&&(g.cap[1]=n,this.matchcap=g.cap.slice(0,this.ncap)),l||this.freeQueue(e,d+1),this.matched=!0;break;case ie.RUNE:R=y.matchRune(i);break;case ie.RUNE1:R=i===y.runes[0];break;case ie.RUNE_ANY:R=!0;break;case ie.RUNE_ANY_NOT_NL:R=i!==V.CODES.get(`
`);break;default:throw new Error("bad inst")}R&&(g=this.add(t,y.out,s,g.cap,o,g)),g!==null&&(this.freeThread(g),e.denseThreads[d]=null)}e.clear()}add(e,t,n,s,i,o){if(t===0||e.contains(t))return o;const u=e.add(t),c=this.prog.inst[t];switch(c.op){case ie.FAIL:break;case ie.ALT:case ie.ALT_MATCH:o=this.add(e,c.out,n,s,i,o),o=this.add(e,c.arg,n,s,i,o);break;case ie.EMPTY_WIDTH:c.arg&~i||(o=this.add(e,c.out,n,s,i,o));break;case ie.NOP:o=this.add(e,c.out,n,s,i,o);break;case ie.CAPTURE:if(c.arg<this.ncap){const l=s[c.arg];s[c.arg]=n,this.add(e,c.out,n,s,i,null),s[c.arg]=l}else o=this.add(e,c.out,n,s,i,o);break;case ie.MATCH:case ie.RUNE:case ie.RUNE1:case ie.RUNE_ANY:case ie.RUNE_ANY_NOT_NL:o===null?o=this.alloc(c):o.inst=c,this.ncap>0&&o.cap!==s&&(o.cap=s.slice(0,this.ncap)),e.denseThreads[u]=o,o=null;break;default:throw new Error("unhandled")}return o}}class Km{constructor(e){this.value=e}get(){return this.value}set(e){this.value=e}compareAndSet(e,t){return this.value===e?(this.value=t,!0):!1}}class Vn{static initTest(e){const t=Vn.compile(e),n=new Vn(t.expr,t.prog,t.numSubexp,t.longest);return n.cond=t.cond,n.prefix=t.prefix,n.prefixUTF8=t.prefixUTF8,n.prefixComplete=t.prefixComplete,n.prefixRune=t.prefixRune,n}static compile(e){return Vn.compileImpl(e,z.PERL,!1)}static compilePOSIX(e){return Vn.compileImpl(e,z.POSIX,!0)}static compileImpl(e,t,n){let s=Nc.parse(e,t);const i=s.maxCap();s=Pt.simplify(s);const o=Ri.compileRegexp(s),u=new Vn(e,o,i,n),[c,l]=o.prefix();return u.prefixComplete=c,u.prefix=l,u.prefixUTF8=re.stringToUtf8ByteArray(u.prefix),u.prefix.length>0&&(u.prefixRune=u.prefix.codePointAt(0)),u.namedGroups=s.namedGroups,u}static match(e,t){return Vn.compile(e).match(t)}constructor(e,t,n=0,s=0){this.expr=e,this.prog=t,this.numSubexp=n,this.longest=s,this.cond=t.startCond(),this.prefix=null,this.prefixUTF8=null,this.prefixComplete=!1,this.prefixRune=0,this.pooled=new Km}numberOfCapturingGroups(){return this.numSubexp}get(){let e;do e=this.pooled.get();while(e&&!this.pooled.compareAndSet(e,e.next));return e}reset(){this.pooled.set(null)}put(e,t){let n=this.pooled.get();do n=this.pooled.get(),!t&&n&&(e=os.fromMachine(e),t=!0),e.next!==n&&(e.next=n);while(!this.pooled.compareAndSet(n,e))}toString(){return this.expr}doExecute(e,t,n,s){let i=this.get(),o=!1;i?i.next!==null&&(i=os.fromMachine(i),o=!0):(i=os.fromRE2(this),o=!0),i.init(s);const u=i.match(e,t,n)?i.submatches():null;return this.put(i,o),u}match(e){return this.doExecute(Oe.fromUTF16(e),0,z.UNANCHORED,0)!==null}matchWithGroup(e,t,n,s,i){return e instanceof Hn||(e=Aa.utf16(e)),this.matchMachineInput(e,t,n,s,i)}matchMachineInput(e,t,n,s,i){if(t>n)return[!1,null];const o=e.isUTF16Encoding()?Oe.fromUTF16(e.asCharSequence(),0,n):Oe.fromUTF8(e.asBytes(),0,n),u=this.doExecute(o,t,s,2*i);return u===null?[!1,null]:[!0,u]}matchUTF8(e){return this.doExecute(Oe.fromUTF8(e),0,z.UNANCHORED,0)!==null}replaceAll(e,t){return this.replaceAllFunc(e,()=>t,2*e.length+1)}replaceFirst(e,t){return this.replaceAllFunc(e,()=>t,1)}replaceAllFunc(e,t,n){let s=0,i=0,o="";const u=Oe.fromUTF16(e);let c=0;for(;i<=e.length;){const l=this.doExecute(u,i,z.UNANCHORED,2);if(l===null||l.length===0)break;o+=e.substring(s,l[0]),(l[1]>s||l[0]===0)&&(o+=t(e.substring(l[0],l[1])),c++),s=l[1];const d=u.step(i)&7;if(i+d>l[1]?i+=d:i+1>l[1]?i++:i=l[1],c>=n)break}return o+=e.substring(s),o}pad(e){if(e===null)return null;let t=(1+this.numSubexp)*2;if(e.length<t){let n=new Array(t).fill(-1);for(let s=0;s<e.length;s++)n[s]=e[s];e=n}return e}allMatches(e,t,n=s=>s){let s=[];const i=e.endPos();t<0&&(t=i+1);let o=0,u=0,c=-1;for(;u<t&&o<=i;){const l=this.doExecute(e,o,z.UNANCHORED,this.prog.numCap);if(l===null||l.length===0)break;let d=!0;if(l[1]===o){l[0]===c&&(d=!1);const g=e.step(o);g<0?o=i+1:o+=g&7}else o=l[1];c=l[1],d&&(s.push(n(this.pad(l))),u++)}return s}findUTF8(e){const t=this.doExecute(Oe.fromUTF8(e),0,z.UNANCHORED,2);return t===null?null:e.slice(t[0],t[1])}findUTF8Index(e){const t=this.doExecute(Oe.fromUTF8(e),0,z.UNANCHORED,2);return t===null?null:t.slice(0,2)}find(e){const t=this.doExecute(Oe.fromUTF16(e),0,z.UNANCHORED,2);return t===null?"":e.substring(t[0],t[1])}findIndex(e){return this.doExecute(Oe.fromUTF16(e),0,z.UNANCHORED,2)}findUTF8Submatch(e){const t=this.doExecute(Oe.fromUTF8(e),0,z.UNANCHORED,this.prog.numCap);if(t===null)return null;const n=new Array(1+this.numSubexp).fill(null);for(let s=0;s<n.length;s++)2*s<t.length&&t[2*s]>=0&&(n[s]=e.slice(t[2*s],t[2*s+1]));return n}findUTF8SubmatchIndex(e){return this.pad(this.doExecute(Oe.fromUTF8(e),0,z.UNANCHORED,this.prog.numCap))}findSubmatch(e){const t=this.doExecute(Oe.fromUTF16(e),0,z.UNANCHORED,this.prog.numCap);if(t===null)return null;const n=new Array(1+this.numSubexp).fill(null);for(let s=0;s<n.length;s++)2*s<t.length&&t[2*s]>=0&&(n[s]=e.substring(t[2*s],t[2*s+1]));return n}findSubmatchIndex(e){return this.pad(this.doExecute(Oe.fromUTF16(e),0,z.UNANCHORED,this.prog.numCap))}findAllUTF8(e,t){const n=this.allMatches(Oe.fromUTF8(e),t,s=>e.slice(s[0],s[1]));return n.length===0?null:n}findAllUTF8Index(e,t){const n=this.allMatches(Oe.fromUTF8(e),t,s=>s.slice(0,2));return n.length===0?null:n}findAll(e,t){const n=this.allMatches(Oe.fromUTF16(e),t,s=>e.substring(s[0],s[1]));return n.length===0?null:n}findAllIndex(e,t){const n=this.allMatches(Oe.fromUTF16(e),t,s=>s.slice(0,2));return n.length===0?null:n}findAllUTF8Submatch(e,t){const n=this.allMatches(Oe.fromUTF8(e),t,s=>{let i=new Array(s.length/2|0).fill(null);for(let o=0;o<i.length;o++)s[2*o]>=0&&(i[o]=e.slice(s[2*o],s[2*o+1]));return i});return n.length===0?null:n}findAllUTF8SubmatchIndex(e,t){const n=this.allMatches(Oe.fromUTF8(e),t);return n.length===0?null:n}findAllSubmatch(e,t){const n=this.allMatches(Oe.fromUTF16(e),t,s=>{let i=new Array(s.length/2|0).fill(null);for(let o=0;o<i.length;o++)s[2*o]>=0&&(i[o]=e.substring(s[2*o],s[2*o+1]));return i});return n.length===0?null:n}findAllSubmatchIndex(e,t){const n=this.allMatches(Oe.fromUTF16(e),t);return n.length===0?null:n}}const ut=class ut{static quote(e){return re.quoteMeta(e)}static compile(e,t=0){let n=e;if(t&ut.CASE_INSENSITIVE&&(n=`(?i)${n}`),t&ut.DOTALL&&(n=`(?s)${n}`),t&ut.MULTILINE&&(n=`(?m)${n}`),t&-32)throw new Bm("Flags should only be a combination of MULTILINE, DOTALL, CASE_INSENSITIVE, DISABLE_UNICODE_GROUPS, LONGEST_MATCH");let s=z.PERL;t&ut.DISABLE_UNICODE_GROUPS&&(s&=-129);const i=new ut(e,t);return i.re2Input=Vn.compileImpl(n,s,(t&ut.LONGEST_MATCH)!==0),i}static matches(e,t){return ut.compile(e).matcher(t).matches()}static initTest(e,t,n){if(e==null)throw new Error("pattern is null");if(n==null)throw new Error("re2 is null");const s=new ut(e,t);return s.re2Input=n,s}constructor(e,t){this.patternInput=e,this.flagsInput=t}reset(){this.re2Input.reset()}flags(){return this.flagsInput}pattern(){return this.patternInput}re2(){return this.re2Input}matches(e){return this.matcher(e).matches()}matcher(e){return Array.isArray(e)&&(e=Aa.utf8(e)),new qm(this,e)}split(e,t=0){const n=this.matcher(e),s=[];let i=0,o=0;for(;n.find();){if(o===0&&n.end()===0){o=n.end();continue}if(t>0&&s.length===t-1)break;if(o===n.start()){if(t===0){i+=1,o=n.end();continue}}else for(;i>0;)s.push(""),i-=1;s.push(n.substring(o,n.start())),o=n.end()}if(t===0&&o!==n.inputLength()){for(;i>0;)s.push(""),i-=1;s.push(n.substring(o,n.inputLength()))}return(t!==0||s.length===0)&&s.push(n.substring(o,n.inputLength())),s}toString(){return this.patternInput}groupCount(){return this.re2Input.numberOfCapturingGroups()}namedGroups(){return this.re2Input.namedGroups}equals(e){return this===e?!0:e===null||this.constructor!==e.constructor?!1:this.flagsInput===e.flagsInput&&this.patternInput===e.patternInput}};_(ut,"CASE_INSENSITIVE",1),_(ut,"DOTALL",2),_(ut,"MULTILINE",4),_(ut,"DISABLE_UNICODE_GROUPS",8),_(ut,"LONGEST_MATCH",16);let Gi=ut;/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class et{constructor(e){this.uid=e}isAuthenticated(){return this.uid!=null}toKey(){return this.isAuthenticated()?"uid:"+this.uid:"anonymous-user"}isEqual(e){return e.uid===this.uid}}et.UNAUTHENTICATED=new et(null),et.GOOGLE_CREDENTIALS=new et("google-credentials-uid"),et.FIRST_PARTY=new et("first-party-uid"),et.MOCK_USER=new et("mock-user");/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */let Ls="12.15.0";function Wm(r){Ls=r}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *//**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Vr=new d1("@firebase/firestore");function ts(){return Vr.logLevel}function L(r,...e){if(Vr.logLevel<=ue.DEBUG){const t=e.map(p1);Vr.debug(`Firestore (${Ls}): ${r}`,...t)}}function ke(r,...e){if(Vr.logLevel<=ue.ERROR){const t=e.map(p1);Vr.error(`Firestore (${Ls}): ${r}`,...t)}}function Lt(r,...e){if(Vr.logLevel<=ue.WARN){const t=e.map(p1);Vr.warn(`Firestore (${Ls}): ${r}`,...t)}}function p1(r){if(typeof r=="string")return r;try{return function(t){return JSON.stringify(t)}(r)}catch{return r}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function j(r,e,t){let n="Unexpected state";typeof e=="string"?n=e:t=e,W2(r,n,t)}function W2(r,e,t){let n=`FIRESTORE (${Ls}) INTERNAL ASSERTION FAILED: ${e} (ID: ${r.toString(16)})`;if(t!==void 0)try{n+=" CONTEXT: "+JSON.stringify(t)}catch{n+=" CONTEXT: "+t}throw ke(n),new Error(n)}function U(r,e,t,n){let s="Unexpected state";typeof t=="string"?s=t:n=t,r||W2(e,s,n)}function H(r,e){return r}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const D={OK:"ok",CANCELLED:"cancelled",UNKNOWN:"unknown",INVALID_ARGUMENT:"invalid-argument",DEADLINE_EXCEEDED:"deadline-exceeded",NOT_FOUND:"not-found",ALREADY_EXISTS:"already-exists",PERMISSION_DENIED:"permission-denied",UNAUTHENTICATED:"unauthenticated",RESOURCE_EXHAUSTED:"resource-exhausted",FAILED_PRECONDITION:"failed-precondition",ABORTED:"aborted",OUT_OF_RANGE:"out-of-range",UNIMPLEMENTED:"unimplemented",INTERNAL:"internal",UNAVAILABLE:"unavailable",DATA_LOSS:"data-loss"};class F extends en{constructor(e,t){super(e,t),this.code=e,this.message=t,this.toString=()=>`${this.name}: [code=${this.code}]: ${this.message}`}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class xt{constructor(){this.promise=new Promise((e,t)=>{this.resolve=e,this.reject=t})}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Qm{constructor(e,t){this.user=t,this.type="OAuth",this.headers=new Map,this.headers.set("Authorization",`Bearer ${e}`)}}class Ym{getToken(){return Promise.resolve(null)}invalidateToken(){}start(e,t){e.enqueueRetryable(()=>t(et.UNAUTHENTICATED))}shutdown(){}}class Xm{constructor(e){this.t=e,this.currentUser=et.UNAUTHENTICATED,this.i=0,this.forceRefresh=!1,this.auth=null}start(e,t){U(this.o===void 0,42304);let n=this.i;const s=c=>this.i!==n?(n=this.i,t(c)):Promise.resolve();let i=new xt;this.o=()=>{this.i++,this.currentUser=this.u(),i.resolve(),i=new xt,e.enqueueRetryable(()=>s(this.currentUser))};const o=()=>{const c=i;e.enqueueRetryable(async()=>{await c.promise,await s(this.currentUser)})},u=c=>{L("FirebaseAuthCredentialsProvider","Auth detected"),this.auth=c,this.o&&(this.auth.addAuthTokenListener(this.o),o())};this.t.onInit(c=>u(c)),setTimeout(()=>{if(!this.auth){const c=this.t.getImmediate({optional:!0});c?u(c):(L("FirebaseAuthCredentialsProvider","Auth not yet detected"),i.resolve(),i=new xt)}},0),o()}getToken(){const e=this.i,t=this.forceRefresh;return this.forceRefresh=!1,this.auth?this.auth.getToken(t).then(n=>this.i!==e?(L("FirebaseAuthCredentialsProvider","getToken aborted due to token change."),this.getToken()):n?(U(typeof n.accessToken=="string",31837,{l:n}),new Qm(n.accessToken,this.currentUser)):null):Promise.resolve(null)}invalidateToken(){this.forceRefresh=!0}shutdown(){this.auth&&this.o&&this.auth.removeAuthTokenListener(this.o),this.o=void 0}u(){const e=this.auth&&this.auth.getUid();return U(e===null||typeof e=="string",2055,{h:e}),new et(e)}}class Jm{constructor(e,t,n){this.T=e,this.P=t,this.R=n,this.type="FirstParty",this.user=et.FIRST_PARTY,this.I=new Map}A(){return this.R?this.R():null}get headers(){this.I.set("X-Goog-AuthUser",this.T);const e=this.A();return e&&this.I.set("Authorization",e),this.P&&this.I.set("X-Goog-Iam-Authorization-Token",this.P),this.I}}class Zm{constructor(e,t,n){this.T=e,this.P=t,this.R=n}getToken(){return Promise.resolve(new Jm(this.T,this.P,this.R))}start(e,t){e.enqueueRetryable(()=>t(et.FIRST_PARTY))}shutdown(){}invalidateToken(){}}class Pd{constructor(e){this.value=e,this.type="AppCheck",this.headers=new Map,e&&e.length>0&&this.headers.set("x-firebase-appcheck",this.value)}}class e4{constructor(e,t){this.V=t,this.forceRefresh=!1,this.appCheck=null,this.m=null,this.p=null,Nt(e)&&e.settings.appCheckToken&&(this.p=e.settings.appCheckToken)}start(e,t){U(this.o===void 0,3512);const n=i=>{i.error!=null&&L("FirebaseAppCheckTokenProvider",`Error getting App Check token; using placeholder token instead. Error: ${i.error.message}`);const o=i.token!==this.m;return this.m=i.token,L("FirebaseAppCheckTokenProvider",`Received ${o?"new":"existing"} token.`),o?t(i.token):Promise.resolve()};this.o=i=>{e.enqueueRetryable(()=>n(i))};const s=i=>{L("FirebaseAppCheckTokenProvider","AppCheck detected"),this.appCheck=i,this.o&&this.appCheck.addTokenListener(this.o)};this.V.onInit(i=>s(i)),setTimeout(()=>{if(!this.appCheck){const i=this.V.getImmediate({optional:!0});i?s(i):L("FirebaseAppCheckTokenProvider","AppCheck not yet detected")}},0)}getToken(){if(this.p)return Promise.resolve(new Pd(this.p));const e=this.forceRefresh;return this.forceRefresh=!1,this.appCheck?this.appCheck.getToken(e).then(t=>t?(U(typeof t.token=="string",44558,{tokenResult:t}),this.m=t.token,new Pd(t.token)):null):Promise.resolve(null)}invalidateToken(){this.forceRefresh=!0}shutdown(){this.appCheck&&this.o&&this.appCheck.removeTokenListener(this.o),this.o=void 0}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function t4(r){const e=typeof self<"u"&&(self.crypto||self.msCrypto),t=new Uint8Array(r);if(e&&typeof e.getRandomValues=="function")e.getRandomValues(t);else for(let n=0;n<r;n++)t[n]=Math.floor(256*Math.random());return t}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class g1{static newId(){const e="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789",t=62*Math.floor(4.129032258064516);let n="";for(;n.length<20;){const s=t4(40);for(let i=0;i<s.length;++i)n.length<20&&s[i]<t&&(n+=e.charAt(s[i]%62))}return n}}function Z(r,e){return r<e?-1:r>e?1:0}function Vc(r,e){const t=Math.min(r.length,e.length);for(let n=0;n<t;n++){const s=r.charAt(n),i=e.charAt(n);if(s!==i)return uc(s)===uc(i)?Z(s,i):uc(s)?1:-1}return Z(r.length,e.length)}const n4=55296,r4=57343;function uc(r){const e=r.charCodeAt(0);return e>=n4&&e<=r4}function hs(r,e,t){return r.length===e.length&&r.every((n,s)=>t(n,e[s]))}function Q2(r){return r+"\0"}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const $t="__name__";class Bt{constructor(e,t,n){t===void 0?t=0:t>e.length&&j(637,{offset:t,range:e.length}),n===void 0?n=e.length-t:n>e.length-t&&j(1746,{length:n,range:e.length-t}),this.segments=e,this.offset=t,this.len=n}get length(){return this.len}isEqual(e){return Bt.comparator(this,e)===0}child(e){const t=this.segments.slice(this.offset,this.limit());return e instanceof Bt?e.forEach(n=>{t.push(n)}):t.push(e),this.construct(t)}limit(){return this.offset+this.length}popFirst(e){return e=e===void 0?1:e,this.construct(this.segments,this.offset+e,this.length-e)}popLast(){return this.construct(this.segments,this.offset,this.length-1)}firstSegment(){return this.segments[this.offset]}lastSegment(){return this.get(this.length-1)}get(e){return this.segments[this.offset+e]}isEmpty(){return this.length===0}isPrefixOf(e){if(e.length<this.length)return!1;for(let t=0;t<this.length;t++)if(this.get(t)!==e.get(t))return!1;return!0}isImmediateParentOf(e){if(this.length+1!==e.length)return!1;for(let t=0;t<this.length;t++)if(this.get(t)!==e.get(t))return!1;return!0}forEach(e){for(let t=this.offset,n=this.limit();t<n;t++)e(this.segments[t])}toArray(){return this.segments.slice(this.offset,this.limit())}static comparator(e,t){const n=Math.min(e.length,t.length);for(let s=0;s<n;s++){const i=Bt.compareSegments(e.get(s),t.get(s));if(i!==0)return i}return Z(e.length,t.length)}static compareSegments(e,t){const n=Bt.isNumericId(e),s=Bt.isNumericId(t);return n&&!s?-1:!n&&s?1:n&&s?Bt.extractNumericId(e).compare(Bt.extractNumericId(t)):Vc(e,t)}static isNumericId(e){return e.startsWith("__id")&&e.endsWith("__")}static extractNumericId(e){return qn.fromString(e.substring(4,e.length-2))}}class ae extends Bt{construct(e,t,n){return new ae(e,t,n)}canonicalString(){return this.toArray().join("/")}toString(){return this.canonicalString()}toStringWithLeadingSlash(){return`/${this.canonicalString()}`}toUriEncodedString(){return this.toArray().map(encodeURIComponent).join("/")}static fromString(...e){const t=[];for(const n of e){if(n.indexOf("//")>=0)throw new F(D.INVALID_ARGUMENT,`Invalid segment (${n}). Paths must not contain // in them.`);t.push(...n.split("/").filter(s=>s.length>0))}return new ae(t)}static emptyPath(){return new ae([])}}const s4=/^[_a-zA-Z][_a-zA-Z0-9]*$/;class ve extends Bt{construct(e,t,n){return new ve(e,t,n)}static isValidIdentifier(e){return s4.test(e)}canonicalString(){return this.toArray().map(e=>(e=e.replace(/\\/g,"\\\\").replace(/`/g,"\\`"),ve.isValidIdentifier(e)||(e="`"+e+"`"),e)).join(".")}toString(){return this.canonicalString()}isKeyField(){return this.length===1&&this.get(0)===$t}static keyField(){return new ve([$t])}static fromServerFormat(e){const t=[];let n="",s=0;const i=()=>{if(n.length===0)throw new F(D.INVALID_ARGUMENT,`Invalid field path (${e}). Paths must not be empty, begin with '.', end with '.', or contain '..'`);t.push(n),n=""};let o=!1;for(;s<e.length;){const u=e[s];if(u==="\\"){if(s+1===e.length)throw new F(D.INVALID_ARGUMENT,"Path has trailing escape character: "+e);const c=e[s+1];if(c!=="\\"&&c!=="."&&c!=="`")throw new F(D.INVALID_ARGUMENT,"Path has invalid escape sequence: "+e);n+=c,s+=2}else u==="`"?(o=!o,s++):u!=="."||o?(n+=u,s++):(i(),s++)}if(i(),o)throw new F(D.INVALID_ARGUMENT,"Unterminated ` in path: "+e);return new ve(t)}static emptyPath(){return new ve([])}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class ${constructor(e){this.path=e}static fromPath(e){return new $(ae.fromString(e))}static fromName(e){return new $(ae.fromString(e).popFirst(5))}static empty(){return new $(ae.emptyPath())}get collectionGroup(){return this.path.popLast().lastSegment()}hasCollectionId(e){return this.path.length>=2&&this.path.get(this.path.length-2)===e}getCollectionGroup(){return this.path.get(this.path.length-2)}getCollectionPath(){return this.path.popLast()}isEqual(e){return e!==null&&ae.comparator(this.path,e.path)===0}toString(){return this.path.toString()}static comparator(e,t){return ae.comparator(e.path,t.path)}static isDocumentKey(e){return e.length%2==0}static fromSegments(e){return new $(new ae(e.slice()))}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Y2(r,e,t){if(!t)throw new F(D.INVALID_ARGUMENT,`Function ${r}() cannot be called with an empty ${e}.`)}function i4(r,e,t,n){if(e===!0&&n===!0)throw new F(D.INVALID_ARGUMENT,`${r} and ${t} cannot be used together.`)}function bd(r){if(!$.isDocumentKey(r))throw new F(D.INVALID_ARGUMENT,`Invalid document reference. Document references must have an even number of segments, but ${r} has ${r.length}.`)}function Cd(r){if($.isDocumentKey(r))throw new F(D.INVALID_ARGUMENT,`Invalid collection reference. Collection references must have an odd number of segments, but ${r} has ${r.length}.`)}function fo(r){return typeof r=="object"&&r!==null&&(Object.getPrototypeOf(r)===Object.prototype||Object.getPrototypeOf(r)===null)}function nu(r){if(r===void 0)return"undefined";if(r===null)return"null";if(typeof r=="string")return r.length>20&&(r=`${r.substring(0,20)}...`),JSON.stringify(r);if(typeof r=="number"||typeof r=="boolean")return""+r;if(typeof r=="object"){if(r instanceof Array)return"an array";{const e=function(n){return n.constructor?n.constructor.name:null}(r);return e?`a custom ${e} object`:"an object"}}return typeof r=="function"?"a function":j(12329,{type:typeof r})}function ct(r,e){if("_delegate"in r&&(r=r._delegate),!(r instanceof e)){if(e.name===r.constructor.name)throw new F(D.INVALID_ARGUMENT,"Type does not match the expected instance. Did you pass a reference from a different Firestore SDK?");{const t=nu(r);throw new F(D.INVALID_ARGUMENT,`Expected type '${e.name}', but it was: ${t}`)}}return r}/**
 * @license
 * Copyright 2025 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Me(r,e){const t={typeString:r};return e&&(t.value=e),t}function po(r,e){if(!fo(r))throw new F(D.INVALID_ARGUMENT,"JSON must be an object");let t;for(const n in e)if(e[n]){const s=e[n].typeString,i="value"in e[n]?{value:e[n].value}:void 0;if(!(n in r)){t=`JSON missing required field: '${n}'`;break}const o=r[n];if(s&&typeof o!==s){t=`JSON field '${n}' must be a ${s}.`;break}if(i!==void 0&&o!==i.value){t=`Expected '${n}' field to equal '${i.value}'`;break}}if(t)throw new F(D.INVALID_ARGUMENT,t);return!0}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Nd=-62135596800,Vd=1e6;class me{static now(){return me.fromMillis(Date.now())}static fromDate(e){return me.fromMillis(e.getTime())}static fromMillis(e){const t=Math.floor(e/1e3),n=Math.floor((e-1e3*t)*Vd);return new me(t,n)}constructor(e,t){if(this.seconds=e,this.nanoseconds=t,t<0)throw new F(D.INVALID_ARGUMENT,"Timestamp nanoseconds out of range: "+t);if(t>=1e9)throw new F(D.INVALID_ARGUMENT,"Timestamp nanoseconds out of range: "+t);if(e<Nd)throw new F(D.INVALID_ARGUMENT,"Timestamp seconds out of range: "+e);if(e>=253402300800)throw new F(D.INVALID_ARGUMENT,"Timestamp seconds out of range: "+e)}toDate(){return new Date(this.toMillis())}toMillis(){return 1e3*this.seconds+this.nanoseconds/Vd}_compareTo(e){return this.seconds===e.seconds?Z(this.nanoseconds,e.nanoseconds):Z(this.seconds,e.seconds)}isEqual(e){return e.seconds===this.seconds&&e.nanoseconds===this.nanoseconds}toString(){return"Timestamp(seconds="+this.seconds+", nanoseconds="+this.nanoseconds+")"}toJSON(){return{type:me._jsonSchemaVersion,seconds:this.seconds,nanoseconds:this.nanoseconds}}static fromJSON(e){if(po(e,me._jsonSchema))return new me(e.seconds,e.nanoseconds)}valueOf(){const e=this.seconds-Nd;return String(e).padStart(12,"0")+"."+String(this.nanoseconds).padStart(9,"0")}}me._jsonSchemaVersion="firestore/timestamp/1.0",me._jsonSchema={type:Me("string",me._jsonSchemaVersion),seconds:Me("number"),nanoseconds:Me("number")};/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class K{static fromTimestamp(e){return new K(e)}static min(){return new K(new me(0,0))}static max(){return new K(new me(253402300799,999999999))}constructor(e){this.timestamp=e}compareTo(e){return this.timestamp._compareTo(e.timestamp)}isEqual(e){return this.timestamp.isEqual(e.timestamp)}toMicroseconds(){return 1e6*this.timestamp.seconds+this.timestamp.nanoseconds/1e3}toString(){return"SnapshotVersion("+this.timestamp.toString()+")"}toTimestamp(){return this.timestamp}}/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const ds=-1;class va{constructor(e,t,n,s){this.indexId=e,this.collectionGroup=t,this.fields=n,this.indexState=s}}function xc(r){return r.fields.find(e=>e.kind===2)}function fr(r){return r.fields.filter(e=>e.kind!==2)}va.UNKNOWN_ID=-1;class aa{constructor(e,t){this.fieldPath=e,this.kind=t}}class ji{constructor(e,t){this.sequenceNumber=e,this.offset=t}static empty(){return new ji(0,Tt.min())}}function X2(r,e){const t=r.toTimestamp().seconds,n=r.toTimestamp().nanoseconds+1,s=K.fromTimestamp(n===1e9?new me(t+1,0):new me(t,n));return new Tt(s,$.empty(),e)}function J2(r){return new Tt(r.readTime,r.key,ds)}class Tt{constructor(e,t,n){this.readTime=e,this.documentKey=t,this.largestBatchId=n}static min(){return new Tt(K.min(),$.empty(),ds)}static max(){return new Tt(K.max(),$.empty(),ds)}}function m1(r,e){let t=r.readTime.compareTo(e.readTime);return t!==0?t:(t=$.comparator(r.documentKey,e.documentKey),t!==0?t:Z(r.largestBatchId,e.largestBatchId))}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Z2="The current tab is not in the required state to perform this operation. It might be necessary to refresh the browser tab.";class e6{constructor(){this.onCommittedListeners=[]}addOnCommittedListener(e){this.onCommittedListeners.push(e)}raiseOnCommittedEvent(){this.onCommittedListeners.forEach(e=>e())}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function tr(r){if(r.code!==D.FAILED_PRECONDITION||r.message!==Z2)throw r;L("LocalStore","Unexpectedly lost primary lease")}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class P{constructor(e){this.nextCallback=null,this.catchCallback=null,this.result=void 0,this.error=void 0,this.isDone=!1,this.callbackAttached=!1,e(t=>{this.isDone=!0,this.result=t,this.nextCallback&&this.nextCallback(t)},t=>{this.isDone=!0,this.error=t,this.catchCallback&&this.catchCallback(t)})}catch(e){return this.next(void 0,e)}next(e,t){return this.callbackAttached&&j(59440),this.callbackAttached=!0,this.isDone?this.error?this.wrapFailure(t,this.error):this.wrapSuccess(e,this.result):new P((n,s)=>{this.nextCallback=i=>{this.wrapSuccess(e,i).next(n,s)},this.catchCallback=i=>{this.wrapFailure(t,i).next(n,s)}})}toPromise(){return new Promise((e,t)=>{this.next(e,t)})}wrapUserFunction(e){try{const t=e();return t instanceof P?t:P.resolve(t)}catch(t){return P.reject(t)}}wrapSuccess(e,t){return e?this.wrapUserFunction(()=>e(t)):P.resolve(t)}wrapFailure(e,t){return e?this.wrapUserFunction(()=>e(t)):P.reject(t)}static resolve(e){return new P((t,n)=>{t(e)})}static reject(e){return new P((t,n)=>{n(e)})}static waitFor(e){return new P((t,n)=>{let s=0,i=0,o=!1;e.forEach(u=>{++s,u.next(()=>{++i,o&&i===s&&t()},c=>n(c))}),o=!0,i===s&&t()})}static or(e){let t=P.resolve(!1);for(const n of e)t=t.next(s=>s?P.resolve(s):n());return t}static forEach(e,t){const n=[];return e.forEach((s,i)=>{n.push(t.call(this,s,i))}),this.waitFor(n)}static mapArray(e,t){return new P((n,s)=>{const i=e.length,o=new Array(i);let u=0;for(let c=0;c<i;c++){const l=c;t(e[l]).next(d=>{o[l]=d,++u,u===i&&n(o)},d=>s(d))}})}static doWhile(e,t){return new P((n,s)=>{const i=()=>{e()===!0?t().next(()=>{i()},s):n()};i()})}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const yt="SimpleDb";class ru{static open(e,t,n,s){try{return new ru(t,e.transaction(s,n))}catch(i){throw new Pi(t,i)}}constructor(e,t){this.action=e,this.transaction=t,this.aborted=!1,this.v=new xt,this.transaction.oncomplete=()=>{this.v.resolve()},this.transaction.onabort=()=>{t.error?this.v.reject(new Pi(e,t.error)):this.v.resolve()},this.transaction.onerror=n=>{const s=_1(n.target.error);this.v.reject(new Pi(e,s))}}get S(){return this.v.promise}abort(e){e&&this.v.reject(e),this.aborted||(L(yt,"Aborting transaction:",e?e.message:"Client-initiated abort"),this.aborted=!0,this.transaction.abort())}D(){const e=this.transaction;this.aborted||typeof e.commit!="function"||e.commit()}store(e){const t=this.transaction.objectStore(e);return new a4(t)}}class $n{static delete(e){return L(yt,"Removing database:",e),gr(b2().indexedDB.deleteDatabase(e)).toPromise()}static C(){if(!h1())return!1;if($n.F())return!0;const e=qe(),t=$n.O(e),n=0<t&&t<10,s=t6(e),i=0<s&&s<4.5;return!(e.indexOf("MSIE ")>0||e.indexOf("Trident/")>0||e.indexOf("Edge/")>0||n||i)}static F(){var e;return typeof process<"u"&&((e=process.__PRIVATE_env)==null?void 0:e.__PRIVATE_USE_MOCK_PERSISTENCE)==="YES"}static M(e,t){return e.store(t)}static O(e){const t=e.match(/i(?:phone|pad|pod) os ([\d_]+)/i),n=t?t[1].split("_").slice(0,2).join("."):"-1";return Number(n)}constructor(e,t,n){this.name=e,this.version=t,this.N=n,this.L=null,$n.O(qe())===12.2&&ke("Firestore persistence suffers from a bug in iOS 12.2 Safari that may cause your app to stop working. See https://stackoverflow.com/q/56496296/110915 for details and a potential workaround.")}async B(e){return this.db||(L(yt,"Opening database:",this.name),this.db=await new Promise((t,n)=>{const s=indexedDB.open(this.name,this.version);s.onsuccess=i=>{const o=i.target.result;t(o)},s.onblocked=()=>{n(new Pi(e,"Cannot upgrade IndexedDB schema while another tab is open. Close all tabs that access Firestore and reload this page to proceed."))},s.onerror=i=>{const o=i.target.error;o.name==="VersionError"?n(new F(D.FAILED_PRECONDITION,"A newer version of the Firestore SDK was previously used and so the persisted data is not compatible with the version of the SDK you are now using. The SDK will operate with persistence disabled. If you need persistence, please re-upgrade to a newer version of the SDK or else clear the persisted IndexedDB data for your app to start fresh.")):o.name==="InvalidStateError"?n(new F(D.FAILED_PRECONDITION,"Unable to open an IndexedDB connection. This could be due to running in a private browsing session on a browser whose private browsing sessions do not support IndexedDB: "+o)):n(new Pi(e,o))},s.onupgradeneeded=i=>{L(yt,'Database "'+this.name+'" requires upgrade from version:',i.oldVersion);const o=i.target.result;this.N.U(o,s.transaction,i.oldVersion,this.version).next(()=>{L(yt,"Database upgrade to version "+this.version+" complete")})}})),this.k&&(this.db.onversionchange=t=>this.k(t)),this.db}q(e){this.k=e,this.db&&(this.db.onversionchange=t=>e(t))}async runTransaction(e,t,n,s){const i=t==="readonly";let o=0;for(;;){++o;try{this.db=await this.B(e);const u=ru.open(this.db,e,i?"readonly":"readwrite",n),c=s(u).next(l=>(u.D(),l)).catch(l=>(u.abort(l),P.reject(l))).toPromise();return c.catch(()=>{}),await u.S,c}catch(u){const c=u,l=c.name!=="FirebaseError"&&o<3;if(L(yt,"Transaction failed with error:",c.message,"Retrying:",l),this.close(),!l)return Promise.reject(c)}}}close(){this.db&&this.db.close(),this.db=void 0}}function t6(r){const e=r.match(/Android ([\d.]+)/i),t=e?e[1].split(".").slice(0,2).join("."):"-1";return Number(t)}class o4{constructor(e){this.$=e,this.K=!1,this.W=null}get isDone(){return this.K}get G(){return this.W}set cursor(e){this.$=e}done(){this.K=!0}j(e){this.W=e}delete(){return gr(this.$.delete())}}class Pi extends F{constructor(e,t){super(D.UNAVAILABLE,`IndexedDB transaction '${e}' failed: ${t}`),this.name="IndexedDbTransactionError"}}function nr(r){return r.name==="IndexedDbTransactionError"}class a4{constructor(e){this.store=e}put(e,t){let n;return t!==void 0?(L(yt,"PUT",this.store.name,e,t),n=this.store.put(t,e)):(L(yt,"PUT",this.store.name,"<auto-key>",e),n=this.store.put(e)),gr(n)}add(e){return L(yt,"ADD",this.store.name,e,e),gr(this.store.add(e))}get(e){return gr(this.store.get(e)).next(t=>(t===void 0&&(t=null),L(yt,"GET",this.store.name,e,t),t))}delete(e){return L(yt,"DELETE",this.store.name,e),gr(this.store.delete(e))}count(){return L(yt,"COUNT",this.store.name),gr(this.store.count())}H(e,t){const n=this.options(e,t),s=n.index?this.store.index(n.index):this.store;if(typeof s.getAll=="function"){const i=s.getAll(n.range);return new P((o,u)=>{i.onerror=c=>{u(c.target.error)},i.onsuccess=c=>{o(c.target.result)}})}{const i=this.cursor(n),o=[];return this.J(i,(u,c)=>{o.push(c)}).next(()=>o)}}Y(e,t){const n=this.store.getAll(e,t===null?void 0:t);return new P((s,i)=>{n.onerror=o=>{i(o.target.error)},n.onsuccess=o=>{s(o.target.result)}})}Z(e,t){L(yt,"DELETE ALL",this.store.name);const n=this.options(e,t);n.X=!1;const s=this.cursor(n);return this.J(s,(i,o,u)=>u.delete())}ee(e,t){let n;t?n=e:(n={},t=e);const s=this.cursor(n);return this.J(s,t)}te(e){const t=this.cursor({});return new P((n,s)=>{t.onerror=i=>{const o=_1(i.target.error);s(o)},t.onsuccess=i=>{const o=i.target.result;o?e(o.primaryKey,o.value).next(u=>{u?o.continue():n()}):n()}})}J(e,t){const n=[];return new P((s,i)=>{e.onerror=o=>{i(o.target.error)},e.onsuccess=o=>{const u=o.target.result;if(!u)return void s();const c=new o4(u),l=t(u.primaryKey,u.value,c);if(l instanceof P){const d=l.catch(g=>(c.done(),P.reject(g)));n.push(d)}c.isDone?s():c.G===null?u.continue():u.continue(c.G)}}).next(()=>P.waitFor(n))}options(e,t){let n;return e!==void 0&&(typeof e=="string"?n=e:t=e),{index:n,range:t}}cursor(e){let t="next";if(e.reverse&&(t="prev"),e.index){const n=this.store.index(e.index);return e.X?n.openKeyCursor(e.range,t):n.openCursor(e.range,t)}return this.store.openCursor(e.range,t)}}function gr(r){return new P((e,t)=>{r.onsuccess=n=>{const s=n.target.result;e(s)},r.onerror=n=>{const s=_1(n.target.error);t(s)}})}let xd=!1;function _1(r){const e=$n.O(qe());if(e>=12.2&&e<13){const t="An internal error was encountered in the Indexed Database server";if(r.message.indexOf(t)>=0){const n=new F("internal",`IOS_INDEXEDDB_BUG1: IndexedDb has thrown '${t}'. This is likely due to an unavoidable bug in iOS. See https://stackoverflow.com/q/56496296/110915 for details and a potential workaround.`);return xd||(xd=!0,setTimeout(()=>{throw n},0)),n}}return r}const bi="IndexBackfiller";class u4{constructor(e,t){this.asyncQueue=e,this.ne=t,this.task=null}start(){this.re(15e3)}stop(){this.task&&(this.task.cancel(),this.task=null)}get started(){return this.task!==null}re(e){L(bi,`Scheduled in ${e}ms`),this.task=this.asyncQueue.enqueueAfterDelay("index_backfill",e,async()=>{this.task=null;try{const t=await this.ne.ie();L(bi,`Documents written: ${t}`)}catch(t){nr(t)?L(bi,"Ignoring IndexedDB error during index backfill: ",t):await tr(t)}await this.re(6e4)})}}class c4{constructor(e,t){this.localStore=e,this.persistence=t}async ie(e=50){return this.persistence.runTransaction("Backfill Indexes","readwrite-primary",t=>this.se(t,e))}se(e,t){const n=new Set;let s=t,i=!0;return P.doWhile(()=>i===!0&&s>0,()=>this.localStore.indexManager.getNextCollectionGroupToUpdate(e).next(o=>{if(o!==null&&!n.has(o))return L(bi,`Processing collection: ${o}`),this._e(e,o,s).next(u=>{s-=u,n.add(o)});i=!1})).next(()=>t-s)}_e(e,t,n){return this.localStore.indexManager.getMinOffsetFromCollectionGroup(e,t).next(s=>this.localStore.localDocuments.getNextDocuments(e,t,s,n).next(i=>{const o=i.changes;return this.localStore.indexManager.updateIndexEntries(e,o).next(()=>this.oe(s,i)).next(u=>(L(bi,`Updating offset: ${u}`),this.localStore.indexManager.updateCollectionGroup(e,t,u))).next(()=>o.size)}))}oe(e,t){let n=e;return t.changes.forEach((s,i)=>{const o=J2(i);m1(o,n)>0&&(n=o)}),new Tt(n.readTime,n.documentKey,Math.max(t.batchId,e.largestBatchId))}}/**
 * @license
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class ht{constructor(e,t){this.previousValue=e,t&&(t.sequenceNumberHandler=n=>this.ae(n),this.ue=n=>t.writeSequenceNumber(n))}ae(e){return this.previousValue=Math.max(e,this.previousValue),this.previousValue}next(){const e=++this.previousValue;return this.ue&&this.ue(e),e}}ht.ce=-1;/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const vr=-1;function go(r){return r==null}function fs(r){return r===0&&1/r==-1/0}function n6(r){return typeof r=="number"&&Number.isInteger(r)&&!fs(r)&&r<=Number.MAX_SAFE_INTEGER&&r>=Number.MIN_SAFE_INTEGER}function l4(r){return typeof r=="string"}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Ra="";function rt(r){let e="";for(let t=0;t<r.length;t++)e.length>0&&(e=Dd(e)),e=h4(r.get(t),e);return Dd(e)}function h4(r,e){let t=e;const n=r.length;for(let s=0;s<n;s++){const i=r.charAt(s);switch(i){case"\0":t+="";break;case Ra:t+="";break;default:t+=i}}return t}function Dd(r){return r+Ra+""}function Gt(r){const e=r.length;if(U(e>=2,64408,{path:r}),e===2)return U(r.charAt(0)===Ra&&r.charAt(1)==="",56145,{path:r}),ae.emptyPath();const t=e-2,n=[];let s="";for(let i=0;i<e;){const o=r.indexOf(Ra,i);switch((o<0||o>t)&&j(50515,{path:r}),r.charAt(o+1)){case"":const u=r.substring(i,o);let c;s.length===0?c=u:(s+=u,c=s,s=""),n.push(c);break;case"":s+=r.substring(i,o),s+="\0";break;case"":s+=r.substring(i,o+1);break;default:j(61167,{path:r})}i=o+2}return new ae(n)}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const pr="remoteDocuments",mo="owner",Wr="owner",zi="mutationQueues",d4="userId",bt="mutations",Od="batchId",Ir="userMutationsIndex",kd=["userId","batchId"];/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function ua(r,e){return[r,rt(e)]}function r6(r,e,t){return[r,rt(e),t]}const f4={},ps="documentMutations",Sa="remoteDocumentsV14",p4=["prefixPath","collectionGroup","readTime","documentId"],ca="documentKeyIndex",g4=["prefixPath","collectionGroup","documentId"],s6="collectionGroupIndex",m4=["collectionGroup","readTime","prefixPath","documentId"],Hi="remoteDocumentGlobal",Dc="remoteDocumentGlobalKey",gs="targets",i6="queryTargetsIndex",_4=["canonicalId","targetId"],ms="targetDocuments",y4=["targetId","path"],y1="documentTargetsIndex",E4=["path","targetId"],Pa="targetGlobalKey",Rr="targetGlobal",Ki="collectionParents",I4=["collectionId","parent"],_s="clientMetadata",w4="clientId",su="bundles",T4="bundleId",iu="namedQueries",A4="name",E1="indexConfiguration",v4="indexId",Oc="collectionGroupIndex",R4="collectionGroup",Ci="indexState",S4=["indexId","uid"],o6="sequenceNumberIndex",P4=["uid","sequenceNumber"],Ni="indexEntries",b4=["indexId","uid","arrayValue","directionalValue","orderedDocumentKey","documentKey"],a6="documentKeyIndex",C4=["indexId","uid","orderedDocumentKey"],ou="documentOverlays",N4=["userId","collectionPath","documentId"],kc="collectionPathOverlayIndex",V4=["userId","collectionPath","largestBatchId"],u6="collectionGroupOverlayIndex",x4=["userId","collectionGroup","largestBatchId"],I1="globals",D4="name",c6=[zi,bt,ps,pr,gs,mo,Rr,ms,_s,Hi,Ki,su,iu],O4=[...c6,ou],l6=[zi,bt,ps,Sa,gs,mo,Rr,ms,_s,Hi,Ki,su,iu,ou],h6=l6,w1=[...h6,E1,Ci,Ni],k4=w1,d6=[...w1,I1],L4=d6;/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Lc extends e6{constructor(e,t){super(),this.le=e,this.currentSequenceNumber=t}}function $e(r,e){const t=H(r);return $n.M(t.le,e)}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Ie{constructor(e,t){this.comparator=e,this.root=t||We.EMPTY}insert(e,t){return new Ie(this.comparator,this.root.insert(e,t,this.comparator).copy(null,null,We.BLACK,null,null))}remove(e){return new Ie(this.comparator,this.root.remove(e,this.comparator).copy(null,null,We.BLACK,null,null))}get(e){let t=this.root;for(;!t.isEmpty();){const n=this.comparator(e,t.key);if(n===0)return t.value;n<0?t=t.left:n>0&&(t=t.right)}return null}indexOf(e){let t=0,n=this.root;for(;!n.isEmpty();){const s=this.comparator(e,n.key);if(s===0)return t+n.left.size;s<0?n=n.left:(t+=n.left.size+1,n=n.right)}return-1}isEmpty(){return this.root.isEmpty()}get size(){return this.root.size}minKey(){return this.root.minKey()}maxKey(){return this.root.maxKey()}inorderTraversal(e){return this.root.inorderTraversal(e)}forEach(e){this.inorderTraversal((t,n)=>(e(t,n),!1))}toString(){const e=[];return this.inorderTraversal((t,n)=>(e.push(`${t}:${n}`),!1)),`{${e.join(", ")}}`}reverseTraversal(e){return this.root.reverseTraversal(e)}getIterator(){return new Yo(this.root,null,this.comparator,!1)}getIteratorFrom(e){return new Yo(this.root,e,this.comparator,!1)}getReverseIterator(){return new Yo(this.root,null,this.comparator,!0)}getReverseIteratorFrom(e){return new Yo(this.root,e,this.comparator,!0)}}class Yo{constructor(e,t,n,s){this.isReverse=s,this.nodeStack=[];let i=1;for(;!e.isEmpty();)if(i=t?n(e.key,t):1,t&&s&&(i*=-1),i<0)e=this.isReverse?e.left:e.right;else{if(i===0){this.nodeStack.push(e);break}this.nodeStack.push(e),e=this.isReverse?e.right:e.left}}getNext(){let e=this.nodeStack.pop();const t={key:e.key,value:e.value};if(this.isReverse)for(e=e.left;!e.isEmpty();)this.nodeStack.push(e),e=e.right;else for(e=e.right;!e.isEmpty();)this.nodeStack.push(e),e=e.left;return t}hasNext(){return this.nodeStack.length>0}peek(){if(this.nodeStack.length===0)return null;const e=this.nodeStack[this.nodeStack.length-1];return{key:e.key,value:e.value}}}class We{constructor(e,t,n,s,i){this.key=e,this.value=t,this.color=n??We.RED,this.left=s??We.EMPTY,this.right=i??We.EMPTY,this.size=this.left.size+1+this.right.size}copy(e,t,n,s,i){return new We(e??this.key,t??this.value,n??this.color,s??this.left,i??this.right)}isEmpty(){return!1}inorderTraversal(e){return this.left.inorderTraversal(e)||e(this.key,this.value)||this.right.inorderTraversal(e)}reverseTraversal(e){return this.right.reverseTraversal(e)||e(this.key,this.value)||this.left.reverseTraversal(e)}min(){return this.left.isEmpty()?this:this.left.min()}minKey(){return this.min().key}maxKey(){return this.right.isEmpty()?this.key:this.right.maxKey()}insert(e,t,n){let s=this;const i=n(e,s.key);return s=i<0?s.copy(null,null,null,s.left.insert(e,t,n),null):i===0?s.copy(null,t,null,null,null):s.copy(null,null,null,null,s.right.insert(e,t,n)),s.fixUp()}removeMin(){if(this.left.isEmpty())return We.EMPTY;let e=this;return e.left.isRed()||e.left.left.isRed()||(e=e.moveRedLeft()),e=e.copy(null,null,null,e.left.removeMin(),null),e.fixUp()}remove(e,t){let n,s=this;if(t(e,s.key)<0)s.left.isEmpty()||s.left.isRed()||s.left.left.isRed()||(s=s.moveRedLeft()),s=s.copy(null,null,null,s.left.remove(e,t),null);else{if(s.left.isRed()&&(s=s.rotateRight()),s.right.isEmpty()||s.right.isRed()||s.right.left.isRed()||(s=s.moveRedRight()),t(e,s.key)===0){if(s.right.isEmpty())return We.EMPTY;n=s.right.min(),s=s.copy(n.key,n.value,null,null,s.right.removeMin())}s=s.copy(null,null,null,null,s.right.remove(e,t))}return s.fixUp()}isRed(){return this.color}fixUp(){let e=this;return e.right.isRed()&&!e.left.isRed()&&(e=e.rotateLeft()),e.left.isRed()&&e.left.left.isRed()&&(e=e.rotateRight()),e.left.isRed()&&e.right.isRed()&&(e=e.colorFlip()),e}moveRedLeft(){let e=this.colorFlip();return e.right.left.isRed()&&(e=e.copy(null,null,null,null,e.right.rotateRight()),e=e.rotateLeft(),e=e.colorFlip()),e}moveRedRight(){let e=this.colorFlip();return e.left.left.isRed()&&(e=e.rotateRight(),e=e.colorFlip()),e}rotateLeft(){const e=this.copy(null,null,We.RED,null,this.right.left);return this.right.copy(null,null,this.color,e,null)}rotateRight(){const e=this.copy(null,null,We.RED,this.left.right,null);return this.left.copy(null,null,this.color,null,e)}colorFlip(){const e=this.left.copy(null,null,!this.left.color,null,null),t=this.right.copy(null,null,!this.right.color,null,null);return this.copy(null,null,!this.color,e,t)}checkMaxDepth(){const e=this.check();return Math.pow(2,e)<=this.size+1}check(){if(this.isRed()&&this.left.isRed())throw j(43730,{key:this.key,value:this.value});if(this.right.isRed())throw j(14113,{key:this.key,value:this.value});const e=this.left.check();if(e!==this.right.check())throw j(27949);return e+(this.isRed()?0:1)}}We.EMPTY=null,We.RED=!0,We.BLACK=!1;We.EMPTY=new class{constructor(){this.size=0}get key(){throw j(57766)}get value(){throw j(16141)}get color(){throw j(16727)}get left(){throw j(29726)}get right(){throw j(36894)}copy(e,t,n,s,i){return this}insert(e,t,n){return new We(e,t)}remove(e,t){return this}isEmpty(){return!0}inorderTraversal(e){return!1}reverseTraversal(e){return!1}minKey(){return null}maxKey(){return null}isRed(){return!1}checkMaxDepth(){return!0}check(){return 0}};/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class ge{constructor(e){this.comparator=e,this.data=new Ie(this.comparator)}has(e){return this.data.get(e)!==null}first(){return this.data.minKey()}last(){return this.data.maxKey()}get size(){return this.data.size}indexOf(e){return this.data.indexOf(e)}forEach(e){this.data.inorderTraversal((t,n)=>(e(t),!1))}forEachInRange(e,t){const n=this.data.getIteratorFrom(e[0]);for(;n.hasNext();){const s=n.getNext();if(this.comparator(s.key,e[1])>=0)return;t(s.key)}}forEachWhile(e,t){let n;for(n=t!==void 0?this.data.getIteratorFrom(t):this.data.getIterator();n.hasNext();)if(!e(n.getNext().key))return}firstAfterOrEqual(e){const t=this.data.getIteratorFrom(e);return t.hasNext()?t.getNext().key:null}getIterator(){return new Ld(this.data.getIterator())}getIteratorFrom(e){return new Ld(this.data.getIteratorFrom(e))}add(e){return this.copy(this.data.remove(e).insert(e,!0))}delete(e){return this.has(e)?this.copy(this.data.remove(e)):this}isEmpty(){return this.data.isEmpty()}unionWith(e){let t=this;return t.size<e.size&&(t=e,e=this),e.forEach(n=>{t=t.add(n)}),t}isEqual(e){if(!(e instanceof ge)||this.size!==e.size)return!1;const t=this.data.getIterator(),n=e.data.getIterator();for(;t.hasNext();){const s=t.getNext().key,i=n.getNext().key;if(this.comparator(s,i)!==0)return!1}return!0}toArray(){const e=[];return this.forEach(t=>{e.push(t)}),e}toString(){const e=[];return this.forEach(t=>e.push(t)),"SortedSet("+e.toString()+")"}copy(e){const t=new ge(this.comparator);return t.data=e,t}}class Ld{constructor(e){this.iter=e}getNext(){return this.iter.getNext().key}hasNext(){return this.iter.hasNext()}}function Qr(r){return r.hasNext()?r.getNext():void 0}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class dt{constructor(e){this.fields=e,e.sort(ve.comparator)}static empty(){return new dt([])}unionWith(e){let t=new ge(ve.comparator);for(const n of this.fields)t=t.add(n);for(const n of e)t=t.add(n);return new dt(t.toArray())}covers(e){for(const t of this.fields)if(t.isPrefixOf(e))return!0;return!1}isEqual(e){return hs(this.fields,e.fields,(t,n)=>t.isEqual(n))}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function ba(r){let e=0;for(const t in r)Object.prototype.hasOwnProperty.call(r,t)&&e++;return e}function rr(r,e){for(const t in r)Object.prototype.hasOwnProperty.call(r,t)&&e(t,r[t])}function M4(r,e){const t=[];for(const n in r)Object.prototype.hasOwnProperty.call(r,n)&&t.push(e(r[n],n,r));return t}function f6(r){for(const e in r)if(Object.prototype.hasOwnProperty.call(r,e))return!1;return!0}/**
 * @license
 * Copyright 2023 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class p6 extends Error{constructor(){super(...arguments),this.name="Base64DecodeError"}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Ce{constructor(e){this.binaryString=e}static fromBase64String(e){const t=function(s){try{return atob(s)}catch(i){throw typeof DOMException<"u"&&i instanceof DOMException?new p6("Invalid base64 string: "+i):i}}(e);return new Ce(t)}static fromUint8Array(e){const t=function(s){let i="";for(let o=0;o<s.length;++o)i+=String.fromCharCode(s[o]);return i}(e);return new Ce(t)}[Symbol.iterator](){let e=0;return{next:()=>e<this.binaryString.length?{value:this.binaryString.charCodeAt(e++),done:!1}:{value:void 0,done:!0}}}toBase64(){return function(t){return btoa(t)}(this.binaryString)}toUint8Array(){return function(t){const n=new Uint8Array(t.length);for(let s=0;s<t.length;s++)n[s]=t.charCodeAt(s);return n}(this.binaryString)}approximateByteSize(){return 2*this.binaryString.length}compareTo(e){return Z(this.binaryString,e.binaryString)}isEqual(e){return this.binaryString===e.binaryString}}Ce.EMPTY_BYTE_STRING=new Ce("");const F4=new RegExp(/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:\.(\d+))?Z$/);function hn(r){if(U(!!r,39018),typeof r=="string"){let e=0;const t=F4.exec(r);if(U(!!t,46558,{timestamp:r}),t[1]){let s=t[1];s=(s+"000000000").substr(0,9),e=Number(s)}const n=new Date(r);return{seconds:Math.floor(n.getTime()/1e3),nanos:e}}return{seconds:we(r.seconds),nanos:we(r.nanos)}}function we(r){return typeof r=="number"?r:typeof r=="string"?Number(r):0}function dn(r){return typeof r=="string"?Ce.fromBase64String(r):Ce.fromUint8Array(r)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const g6="server_timestamp",m6="__type__",_6="__previous_value__",y6="__local_write_time__";function au(r){var t,n;return((n=(((t=r==null?void 0:r.mapValue)==null?void 0:t.fields)||{})[m6])==null?void 0:n.stringValue)===g6}function _o(r){const e=r.mapValue.fields[_6];return au(e)?_o(e):e}function ys(r){const e=hn(r.mapValue.fields[y6].timestampValue);return new me(e.seconds,e.nanos)}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class U4{constructor(e,t,n,s,i,o,u,c,l,d,g){this.databaseId=e,this.appId=t,this.persistenceKey=n,this.host=s,this.ssl=i,this.forceLongPolling=o,this.autoDetectLongPolling=u,this.longPollingOptions=c,this.useFetchStreams=l,this.isUsingEmulator=d,this.apiKey=g}}const Ca="(default)";class xr{constructor(e,t){this.projectId=e,this.database=t||Ca}static empty(){return new xr("","")}get isDefaultDatabase(){return this.database===Ca}isEqual(e){return e instanceof xr&&e.projectId===this.projectId&&e.database===this.database}}function B4(r,e){if(!Object.prototype.hasOwnProperty.apply(r.options,["projectId"]))throw new F(D.INVALID_ARGUMENT,'"projectId" not provided in firebase.initializeApp.');return new xr(r.options.projectId,e)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const T1="__type__",E6="__max__",Fn={mapValue:{fields:{__type__:{stringValue:E6}}}},A1="__vector__",Dr="value",Kt={nullValue:"NULL_VALUE"},pt={booleanValue:!0},He={booleanValue:!1};function Fe(r){return"nullValue"in r?0:"booleanValue"in r?1:"integerValue"in r||"doubleValue"in r?2:"timestampValue"in r?3:"stringValue"in r?5:"bytesValue"in r?6:"referenceValue"in r?7:"geoPointValue"in r?8:"arrayValue"in r?9:"mapValue"in r?au(r)?4:I6(r)?9007199254740991:Or(r)?10:11:j(28295,{value:r})}function St(r,e,t){if(r===e)return!0;const n=Fe(r);if(n!==Fe(e))return!1;switch(n){case 0:case 9007199254740991:return!0;case 1:return r.booleanValue===e.booleanValue;case 4:return ys(r).isEqual(ys(e));case 3:return function(i,o){if(typeof i.timestampValue=="string"&&typeof o.timestampValue=="string"&&i.timestampValue.length===o.timestampValue.length)return i.timestampValue===o.timestampValue;const u=hn(i.timestampValue),c=hn(o.timestampValue);return u.seconds===c.seconds&&u.nanos===c.nanos}(r,e);case 5:return r.stringValue===e.stringValue;case 6:return function(i,o){return dn(i.bytesValue).isEqual(dn(o.bytesValue))}(r,e);case 7:return r.referenceValue===e.referenceValue;case 8:return function(i,o){return we(i.geoPointValue.latitude)===we(o.geoPointValue.latitude)&&we(i.geoPointValue.longitude)===we(o.geoPointValue.longitude)}(r,e);case 2:return function(i,o,u){if("integerValue"in i&&"integerValue"in o)return we(i.integerValue)===we(o.integerValue);let c,l;if("doubleValue"in i&&"doubleValue"in o)c=we(i.doubleValue),l=we(o.doubleValue);else{if(!(u!=null&&u.Ee))return!1;c=we(i.integerValue??i.doubleValue),l=we(o.integerValue??o.doubleValue)}return c===l?!!(u!=null&&u.he)||fs(c)===fs(l):!!(u===void 0||u.Te)&&isNaN(c)&&isNaN(l)}(r,e,t);case 9:return hs(r.arrayValue.values||[],e.arrayValue.values||[],(s,i)=>St(s,i,t));case 10:case 11:return function(i,o,u){const c=i.mapValue.fields||{},l=o.mapValue.fields||{};if(ba(c)!==ba(l))return!1;for(const d in c)if(c.hasOwnProperty(d)&&(l[d]===void 0||!St(c[d],l[d],u)))return!1;return!0}(r,e,t);default:return j(52216,{left:r})}}function Wi(r,e){return(r.values||[]).find(t=>St(t,e))!==void 0}function st(r,e){if(r===e)return 0;const t=Fe(r),n=Fe(e);if(t!==n)return Z(t,n);switch(t){case 0:case 9007199254740991:return 0;case 1:return Z(r.booleanValue,e.booleanValue);case 2:return function(i,o){const u=we(i.integerValue||i.doubleValue),c=we(o.integerValue||o.doubleValue);return u<c?-1:u>c?1:u===c?0:isNaN(u)?isNaN(c)?0:-1:1}(r,e);case 3:return Md(r.timestampValue,e.timestampValue);case 4:return Md(ys(r),ys(e));case 5:return Vc(r.stringValue,e.stringValue);case 6:return function(i,o){const u=dn(i),c=dn(o);return u.compareTo(c)}(r.bytesValue,e.bytesValue);case 7:return function(i,o){const u=i.split("/"),c=o.split("/");for(let l=0;l<u.length&&l<c.length;l++){const d=Z(u[l],c[l]);if(d!==0)return d}return Z(u.length,c.length)}(r.referenceValue,e.referenceValue);case 8:return function(i,o){const u=Z(we(i.latitude),we(o.latitude));return u!==0?u:Z(we(i.longitude),we(o.longitude))}(r.geoPointValue,e.geoPointValue);case 9:return Fd(r.arrayValue,e.arrayValue);case 10:return function(i,o){var y,R,C,M;const u=i.fields||{},c=o.fields||{},l=(y=u[Dr])==null?void 0:y.arrayValue,d=(R=c[Dr])==null?void 0:R.arrayValue,g=Z(((C=l==null?void 0:l.values)==null?void 0:C.length)||0,((M=d==null?void 0:d.values)==null?void 0:M.length)||0);return g!==0?g:Fd(l,d)}(r.mapValue,e.mapValue);case 11:return function(i,o){if(i===Fn.mapValue&&o===Fn.mapValue)return 0;if(i===Fn.mapValue)return 1;if(o===Fn.mapValue)return-1;const u=i.fields||{},c=Object.keys(u),l=o.fields||{},d=Object.keys(l);c.sort(),d.sort();for(let g=0;g<c.length&&g<d.length;++g){const y=Vc(c[g],d[g]);if(y!==0)return y;const R=st(u[c[g]],l[d[g]]);if(R!==0)return R}return Z(c.length,d.length)}(r.mapValue,e.mapValue);default:throw j(23264,{Pe:t})}}function Md(r,e){if(typeof r=="string"&&typeof e=="string"&&r.length===e.length)return Z(r,e);const t=hn(r),n=hn(e),s=Z(t.seconds,n.seconds);return s!==0?s:Z(t.nanos,n.nanos)}function Fd(r,e){const t=r.values||[],n=e.values||[];for(let s=0;s<t.length&&s<n.length;++s){const i=st(t[s],n[s]);if(i!==void 0&&i!==0)return i}return Z(t.length,n.length)}function Es(r){return Mc(r)}function Mc(r){return"nullValue"in r?"null":"booleanValue"in r?""+r.booleanValue:"integerValue"in r?""+r.integerValue:"doubleValue"in r?""+r.doubleValue:"timestampValue"in r?function(t){const n=hn(t);return`time(${n.seconds},${n.nanos})`}(r.timestampValue):"stringValue"in r?r.stringValue:"bytesValue"in r?function(t){return dn(t).toBase64()}(r.bytesValue):"referenceValue"in r?function(t){return $.fromName(t).toString()}(r.referenceValue):"geoPointValue"in r?function(t){return`geo(${t.latitude},${t.longitude})`}(r.geoPointValue):"arrayValue"in r?function(t){let n="[",s=!0;for(const i of t.values||[])s?s=!1:n+=",",n+=Mc(i);return n+"]"}(r.arrayValue):"mapValue"in r?function(t){const n=Object.keys(t.fields||{}).sort();let s="{",i=!0;for(const o of n)i?i=!1:s+=",",s+=`${o}:${Mc(t.fields[o])}`;return s+"}"}(r.mapValue):j(61005,{value:r})}function la(r){switch(Fe(r)){case 0:case 1:return 4;case 2:return 8;case 3:case 8:return 16;case 4:const e=_o(r);return e?16+la(e):16;case 5:return 2*r.stringValue.length;case 6:return dn(r.bytesValue).approximateByteSize();case 7:return r.referenceValue.length;case 9:return function(n){return(n.values||[]).reduce((s,i)=>s+la(i),0)}(r.arrayValue);case 10:case 11:return function(n){let s=0;return rr(n.fields,(i,o)=>{s+=i.length+la(o)}),s}(r.mapValue);default:throw j(13486,{value:r})}}function Qi(r,e){return{referenceValue:`projects/${r.projectId}/databases/${r.database}/documents/${e.path.canonicalString()}`}}function jt(r){return!!r&&"integerValue"in r}function wr(r){return!!r&&"doubleValue"in r}function Kn(r){return jt(r)||wr(r)}function Wn(r){return!!r&&"arrayValue"in r}function It(r){return!!r&&"nullValue"in r}function gt(r){return!!r&&"doubleValue"in r&&isNaN(Number(r.doubleValue))}function Sr(r){return!!r&&"mapValue"in r}function Or(r){var t,n;return((n=(((t=r==null?void 0:r.mapValue)==null?void 0:t.fields)||{})[T1])==null?void 0:n.stringValue)===A1}function Fc(r){var e,t;return(t=(((e=r==null?void 0:r.mapValue)==null?void 0:e.fields)||{})[Dr])==null?void 0:t.arrayValue}function Vi(r){if(r.geoPointValue)return{geoPointValue:{...r.geoPointValue}};if(r.timestampValue&&typeof r.timestampValue=="object")return{timestampValue:{...r.timestampValue}};if(r.mapValue){const e={mapValue:{fields:{}}};return rr(r.mapValue.fields,(t,n)=>e.mapValue.fields[t]=Vi(n)),e}if(r.arrayValue){const e={arrayValue:{values:[]}};for(let t=0;t<(r.arrayValue.values||[]).length;++t)e.arrayValue.values[t]=Vi(r.arrayValue.values[t]);return e}return{...r}}function I6(r){return(((r.mapValue||{}).fields||{}).__type__||{}).stringValue===E6}const w6={mapValue:{fields:{[T1]:{stringValue:A1},[Dr]:{arrayValue:{}}}}};function q4(r){return"nullValue"in r?Kt:"booleanValue"in r?{booleanValue:!1}:"integerValue"in r||"doubleValue"in r?{doubleValue:NaN}:"timestampValue"in r?{timestampValue:{seconds:Number.MIN_SAFE_INTEGER}}:"stringValue"in r?{stringValue:""}:"bytesValue"in r?{bytesValue:""}:"referenceValue"in r?Qi(xr.empty(),$.empty()):"geoPointValue"in r?{geoPointValue:{latitude:-90,longitude:-180}}:"arrayValue"in r?{arrayValue:{}}:"mapValue"in r?Or(r)?w6:{mapValue:{}}:j(35942,{value:r})}function $4(r){return"nullValue"in r?{booleanValue:!1}:"booleanValue"in r?{doubleValue:NaN}:"integerValue"in r||"doubleValue"in r?{timestampValue:{seconds:Number.MIN_SAFE_INTEGER}}:"timestampValue"in r?{stringValue:""}:"stringValue"in r?{bytesValue:""}:"bytesValue"in r?Qi(xr.empty(),$.empty()):"referenceValue"in r?{geoPointValue:{latitude:-90,longitude:-180}}:"geoPointValue"in r?{arrayValue:{}}:"arrayValue"in r?w6:"mapValue"in r?Or(r)?{mapValue:{}}:Fn:j(61959,{value:r})}function Ud(r,e){const t=st(r.value,e.value);return t!==0?t:r.inclusive&&!e.inclusive?-1:!r.inclusive&&e.inclusive?1:0}function Bd(r,e){const t=st(r.value,e.value);return t!==0?t:r.inclusive&&!e.inclusive?1:!r.inclusive&&e.inclusive?-1:0}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class ze{constructor(e){this.value=e}static empty(){return new ze({mapValue:{}})}field(e){if(e.isEmpty())return this.value;{let t=this.value;for(let n=0;n<e.length-1;++n)if(t=(t.mapValue.fields||{})[e.get(n)],!Sr(t))return null;return t=(t.mapValue.fields||{})[e.lastSegment()],t||null}}set(e,t){this.getFieldsMap(e.popLast())[e.lastSegment()]=Vi(t)}setAll(e){let t=ve.emptyPath(),n={},s=[];e.forEach((o,u)=>{if(!t.isImmediateParentOf(u)){const c=this.getFieldsMap(t);this.applyChanges(c,n,s),n={},s=[],t=u.popLast()}o?n[u.lastSegment()]=Vi(o):s.push(u.lastSegment())});const i=this.getFieldsMap(t);this.applyChanges(i,n,s)}delete(e){const t=this.field(e.popLast());Sr(t)&&t.mapValue.fields&&delete t.mapValue.fields[e.lastSegment()]}isEqual(e){return St(this.value,e.value)}getFieldsMap(e){let t=this.value;t.mapValue.fields||(t.mapValue={fields:{}});for(let n=0;n<e.length;++n){let s=t.mapValue.fields[e.get(n)];Sr(s)&&s.mapValue.fields||(s={mapValue:{fields:{}}},t.mapValue.fields[e.get(n)]=s),t=s}return t.mapValue.fields}applyChanges(e,t,n){rr(t,(s,i)=>e[s]=i);for(const s of n)delete e[s]}clone(){return new ze(Vi(this.value))}}function T6(r){const e=[];return rr(r.fields,(t,n)=>{const s=new ve([t]);if(Sr(n)){const i=T6(n.mapValue).fields;if(i.length===0)e.push(s);else for(const o of i)e.push(s.child(o))}else e.push(s)}),new dt(e)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function uu(r,e){if(r.useProto3Json){if(isNaN(e))return{doubleValue:"NaN"};if(e===1/0)return{doubleValue:"Infinity"};if(e===-1/0)return{doubleValue:"-Infinity"}}return{doubleValue:fs(e)?"-0":e}}function v1(r){return{integerValue:""+r}}function R1(r,e,t){return Number.isInteger(e)&&(t!=null&&t.preferIntegers)||n6(e)?v1(e):uu(r,e)}/**
 * @license
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class cu{constructor(){this._=void 0}}function G4(r,e,t){return r instanceof Yi?function(s,i){const o={fields:{[m6]:{stringValue:g6},[y6]:{timestampValue:{seconds:s.seconds,nanos:s.nanoseconds}}}};return i&&au(i)&&(i=_o(i)),i&&(o.fields[_6]=i),{mapValue:o}}(t,e):r instanceof Is?v6(r,e):r instanceof ws?R6(r,e):r instanceof Ts?function(s,i){const o=A6(s,i),u=Na(o)+Na(s.Re);return jt(o)&&jt(s.Re)?v1(u):uu(s.serializer,u)}(r,e):r instanceof Xi?function(s,i){return qd(s,i,Math.min)}(r,e):r instanceof Ji?function(s,i){return qd(s,i,Math.max)}(r,e):void 0}function j4(r,e,t){return r instanceof Is?v6(r,e):r instanceof ws?R6(r,e):t}function A6(r,e){return r instanceof Ts?Kn(e)?e:{integerValue:0}:null}class Yi extends cu{}class Is extends cu{constructor(e){super(),this.elements=e}}function v6(r,e){const t=S6(e);for(const n of r.elements)t.some(s=>St(s,n))||t.push(n);return{arrayValue:{values:t}}}class ws extends cu{constructor(e){super(),this.elements=e}}function R6(r,e){let t=S6(e);for(const n of r.elements)t=t.filter(s=>!St(s,n));return{arrayValue:{values:t}}}class S1 extends cu{constructor(e,t){super(),this.serializer=e,this.Re=t}}class Ts extends S1{}class Xi extends S1{}class Ji extends S1{}function qd(r,e,t){if(!Kn(e))return r.Re;const n=t(Na(e),Na(r.Re));return jt(e)&&jt(r.Re)?v1(n):uu(r.serializer,n)}function Na(r){return we(r.integerValue||r.doubleValue)}function S6(r){return Wn(r)&&r.arrayValue.values?r.arrayValue.values.slice():[]}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class z4{constructor(e,t){this.field=e,this.transform=t}}function H4(r,e){return r.field.isEqual(e.field)&&function(n,s){return n instanceof Is&&s instanceof Is||n instanceof ws&&s instanceof ws?hs(n.elements,s.elements,St):n instanceof Ts&&s instanceof Ts||n instanceof Xi&&s instanceof Xi||n instanceof Ji&&s instanceof Ji?St(n.Re,s.Re):n instanceof Yi&&s instanceof Yi}(r.transform,e.transform)}class K4{constructor(e,t){this.version=e,this.transformResults=t}}class Se{constructor(e,t){this.updateTime=e,this.exists=t}static none(){return new Se}static exists(e){return new Se(void 0,e)}static updateTime(e){return new Se(e)}get isNone(){return this.updateTime===void 0&&this.exists===void 0}isEqual(e){return this.exists===e.exists&&(this.updateTime?!!e.updateTime&&this.updateTime.isEqual(e.updateTime):!e.updateTime)}}function ha(r,e){return r.updateTime!==void 0?e.isFoundDocument()&&e.version.isEqual(r.updateTime):r.exists===void 0||r.exists===e.isFoundDocument()}class lu{}function P6(r,e){if(!r.hasLocalMutations||e&&e.fields.length===0)return null;if(e===null)return r.isNoDocument()?new Fs(r.key,Se.none()):new Ms(r.key,r.data,Se.none());{const t=r.data,n=ze.empty();let s=new ge(ve.comparator);for(let i of e.fields)if(!s.has(i)){let o=t.field(i);o===null&&i.length>1&&(i=i.popLast(),o=t.field(i)),o===null?n.delete(i):n.set(i,o),s=s.add(i)}return new yn(r.key,n,new dt(s.toArray()),Se.none())}}function W4(r,e,t){r instanceof Ms?function(s,i,o){const u=s.value.clone(),c=Gd(s.fieldTransforms,i,o.transformResults);u.setAll(c),i.convertToFoundDocument(o.version,u).setHasCommittedMutations()}(r,e,t):r instanceof yn?function(s,i,o){if(!ha(s.precondition,i))return void i.convertToUnknownDocument(o.version);const u=Gd(s.fieldTransforms,i,o.transformResults),c=i.data;c.setAll(b6(s)),c.setAll(u),i.convertToFoundDocument(o.version,c).setHasCommittedMutations()}(r,e,t):function(s,i,o){i.convertToNoDocument(o.version).setHasCommittedMutations()}(0,e,t)}function xi(r,e,t,n){return r instanceof Ms?function(i,o,u,c){if(!ha(i.precondition,o))return u;const l=i.value.clone(),d=jd(i.fieldTransforms,c,o);return l.setAll(d),o.convertToFoundDocument(o.version,l).setHasLocalMutations(),null}(r,e,t,n):r instanceof yn?function(i,o,u,c){if(!ha(i.precondition,o))return u;const l=jd(i.fieldTransforms,c,o),d=o.data;return d.setAll(b6(i)),d.setAll(l),o.convertToFoundDocument(o.version,d).setHasLocalMutations(),u===null?null:u.unionWith(i.fieldMask.fields).unionWith(i.fieldTransforms.map(g=>g.field))}(r,e,t,n):function(i,o,u){return ha(i.precondition,o)?(o.convertToNoDocument(o.version).setHasLocalMutations(),null):u}(r,e,t)}function Q4(r,e){let t=null;for(const n of r.fieldTransforms){const s=e.data.field(n.field),i=A6(n.transform,s||null);i!=null&&(t===null&&(t=ze.empty()),t.set(n.field,i))}return t||null}function $d(r,e){return r.type===e.type&&!!r.key.isEqual(e.key)&&!!r.precondition.isEqual(e.precondition)&&!!function(n,s){return n===void 0&&s===void 0||!(!n||!s)&&hs(n,s,(i,o)=>H4(i,o))}(r.fieldTransforms,e.fieldTransforms)&&(r.type===0?r.value.isEqual(e.value):r.type!==1||r.data.isEqual(e.data)&&r.fieldMask.isEqual(e.fieldMask))}class Ms extends lu{constructor(e,t,n,s=[]){super(),this.key=e,this.value=t,this.precondition=n,this.fieldTransforms=s,this.type=0}getFieldMask(){return null}}class yn extends lu{constructor(e,t,n,s,i=[]){super(),this.key=e,this.data=t,this.fieldMask=n,this.precondition=s,this.fieldTransforms=i,this.type=1}getFieldMask(){return this.fieldMask}}function b6(r){const e=new Map;return r.fieldMask.fields.forEach(t=>{if(!t.isEmpty()){const n=r.data.field(t);e.set(t,n)}}),e}function Gd(r,e,t){const n=new Map;U(r.length===t.length,32656,{Ie:t.length,Ae:r.length});for(let s=0;s<t.length;s++){const i=r[s],o=i.transform,u=e.data.field(i.field);n.set(i.field,j4(o,u,t[s]))}return n}function jd(r,e,t){const n=new Map;for(const s of r){const i=s.transform,o=t.data.field(s.field);n.set(s.field,G4(i,o,e))}return n}class Fs extends lu{constructor(e,t){super(),this.key=e,this.precondition=t,this.type=2,this.fieldTransforms=[]}getFieldMask(){return null}}class P1 extends lu{constructor(e,t){super(),this.key=e,this.precondition=t,this.type=3,this.fieldTransforms=[]}getFieldMask(){return null}}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class As{constructor(e,t){this.position=e,this.inclusive=t}}function zd(r,e,t){let n=0;for(let s=0;s<r.position.length;s++){const i=e[s],o=r.position[s];if(i.field.isKeyField()?n=$.comparator($.fromName(o.referenceValue),t.key):n=st(o,t.data.field(i.field)),i.dir==="desc"&&(n*=-1),n!==0)break}return n}function Hd(r,e){if(r===null)return e===null;if(e===null||r.inclusive!==e.inclusive||r.position.length!==e.position.length)return!1;for(let t=0;t<r.position.length;t++)if(!St(r.position[t],e.position[t]))return!1;return!0}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class C6{}class ce extends C6{constructor(e,t,n){super(),this.field=e,this.op=t,this.value=n}static create(e,t,n){return e.isKeyField()?t==="in"||t==="not-in"?this.createKeyFieldInFilter(e,t,n):new Y4(e,t,n):t==="array-contains"?new Z4(e,n):t==="in"?new k6(e,n):t==="not-in"?new e3(e,n):t==="array-contains-any"?new t3(e,n):new ce(e,t,n)}static createKeyFieldInFilter(e,t,n){return t==="in"?new X4(e,n):new J4(e,n)}matches(e){const t=e.data.field(this.field);return this.op==="!="?t!==null&&t.nullValue===void 0&&this.matchesComparison(st(t,this.value)):t!==null&&Fe(this.value)===Fe(t)&&this.matchesComparison(st(t,this.value))}matchesComparison(e){switch(this.op){case"<":return e<0;case"<=":return e<=0;case"==":return e===0;case"!=":return e!==0;case">":return e>0;case">=":return e>=0;default:return j(47266,{operator:this.op})}}isInequality(){return["<","<=",">",">=","!=","not-in"].indexOf(this.op)>=0}getFlattenedFilters(){return[this]}getFilters(){return[this]}}class _e extends C6{constructor(e,t){super(),this.filters=e,this.op=t,this.Ve=null}static create(e,t){return new _e(e,t)}matches(e){return vs(this)?this.filters.find(t=>!t.matches(e))===void 0:this.filters.find(t=>t.matches(e))!==void 0}getFlattenedFilters(){return this.Ve!==null||(this.Ve=this.filters.reduce((e,t)=>e.concat(t.getFlattenedFilters()),[])),this.Ve}getFilters(){return Object.assign([],this.filters)}}function vs(r){return r.op==="and"}function Uc(r){return r.op==="or"}function b1(r){return N6(r)&&vs(r)}function N6(r){for(const e of r.filters)if(e instanceof _e)return!1;return!0}function Bc(r){if(r instanceof ce)return r.field.canonicalString()+r.op.toString()+Es(r.value);if(b1(r))return r.filters.map(e=>Bc(e)).join(",");{const e=r.filters.map(t=>Bc(t)).join(",");return`${r.op}(${e})`}}function V6(r,e){return r instanceof ce?function(n,s){return s instanceof ce&&n.op===s.op&&n.field.isEqual(s.field)&&St(n.value,s.value)}(r,e):r instanceof _e?function(n,s){return s instanceof _e&&n.op===s.op&&n.filters.length===s.filters.length?n.filters.reduce((i,o,u)=>i&&V6(o,s.filters[u]),!0):!1}(r,e):void j(19439)}function x6(r,e){const t=r.filters.concat(e);return _e.create(t,r.op)}function D6(r){return r instanceof ce?function(t){return`${t.field.canonicalString()} ${t.op} ${Es(t.value)}`}(r):r instanceof _e?function(t){return t.op.toString()+" {"+t.getFilters().map(D6).join(" ,")+"}"}(r):"Filter"}class Y4 extends ce{constructor(e,t,n){super(e,t,n),this.key=$.fromName(n.referenceValue)}matches(e){const t=$.comparator(e.key,this.key);return this.matchesComparison(t)}}class X4 extends ce{constructor(e,t){super(e,"in",t),this.keys=O6("in",t)}matches(e){return this.keys.some(t=>t.isEqual(e.key))}}class J4 extends ce{constructor(e,t){super(e,"not-in",t),this.keys=O6("not-in",t)}matches(e){return!this.keys.some(t=>t.isEqual(e.key))}}function O6(r,e){var t;return(((t=e.arrayValue)==null?void 0:t.values)||[]).map(n=>$.fromName(n.referenceValue))}class Z4 extends ce{constructor(e,t){super(e,"array-contains",t)}matches(e){const t=e.data.field(this.field);return Wn(t)&&Wi(t.arrayValue,this.value)}}class k6 extends ce{constructor(e,t){super(e,"in",t)}matches(e){const t=e.data.field(this.field);return t!==null&&Wi(this.value.arrayValue,t)}}class e3 extends ce{constructor(e,t){super(e,"not-in",t)}matches(e){if(Wi(this.value.arrayValue,{nullValue:"NULL_VALUE"}))return!1;const t=e.data.field(this.field);return t!==null&&t.nullValue===void 0&&!Wi(this.value.arrayValue,t)}}class t3 extends ce{constructor(e,t){super(e,"array-contains-any",t)}matches(e){const t=e.data.field(this.field);return!(!Wn(t)||!t.arrayValue.values)&&t.arrayValue.values.some(n=>Wi(this.value.arrayValue,n))}}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Zi{constructor(e,t="asc"){this.field=e,this.dir=t}}function n3(r,e){return r.dir===e.dir&&r.field.isEqual(e.field)}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Re{constructor(e,t,n,s,i,o,u){this.key=e,this.documentType=t,this.version=n,this.readTime=s,this.createTime=i,this.data=o,this.documentState=u}static newInvalidDocument(e){return new Re(e,0,K.min(),K.min(),K.min(),ze.empty(),0)}static newFoundDocument(e,t,n,s){return new Re(e,1,t,K.min(),n,s,0)}static newNoDocument(e,t){return new Re(e,2,t,K.min(),K.min(),ze.empty(),0)}static newUnknownDocument(e,t){return new Re(e,3,t,K.min(),K.min(),ze.empty(),2)}convertToFoundDocument(e,t){return!this.createTime.isEqual(K.min())||this.documentType!==2&&this.documentType!==0||(this.createTime=e),this.version=e,this.documentType=1,this.data=t,this.documentState=0,this}convertToNoDocument(e){return this.version=e,this.documentType=2,this.data=ze.empty(),this.documentState=0,this}convertToUnknownDocument(e){return this.version=e,this.documentType=3,this.data=ze.empty(),this.documentState=2,this}setHasCommittedMutations(){return this.documentState=2,this}setHasLocalMutations(){return this.documentState=1,this.version=K.min(),this}setReadTime(e){return this.readTime=e,this}get hasLocalMutations(){return this.documentState===1}get hasCommittedMutations(){return this.documentState===2}get hasPendingWrites(){return this.hasLocalMutations||this.hasCommittedMutations}isValidDocument(){return this.documentType!==0}isFoundDocument(){return this.documentType===1}isNoDocument(){return this.documentType===2}isUnknownDocument(){return this.documentType===3}isEqual(e){return e instanceof Re&&this.key.isEqual(e.key)&&this.version.isEqual(e.version)&&this.documentType===e.documentType&&this.documentState===e.documentState&&this.data.isEqual(e.data)}mutableCopy(){return new Re(this.key,this.documentType,this.version,this.readTime,this.createTime,this.data.clone(),this.documentState)}toString(){return`Document(${this.key}, ${this.version}, ${JSON.stringify(this.data.value)}, {createTime: ${this.createTime}}), {documentType: ${this.documentType}}), {documentState: ${this.documentState}})`}}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class r3{constructor(e,t=null,n=[],s=[],i=null,o=null,u=null){this.path=e,this.collectionGroup=t,this.orderBy=n,this.filters=s,this.limit=i,this.startAt=o,this.endAt=u,this.de=null}}function qc(r,e=null,t=[],n=[],s=null,i=null,o=null){return new r3(r,e,t,n,s,i,o)}function Va(r){const e=H(r);if(e.de===null){let t=e.path.canonicalString();e.collectionGroup!==null&&(t+="|cg:"+e.collectionGroup),t+="|f:",t+=e.filters.map(n=>Bc(n)).join(","),t+="|ob:",t+=e.orderBy.map(n=>function(i){return i.field.canonicalString()+i.dir}(n)).join(","),go(e.limit)||(t+="|l:",t+=e.limit),e.startAt&&(t+="|lb:",t+=e.startAt.inclusive?"b:":"a:",t+=e.startAt.position.map(n=>Es(n)).join(",")),e.endAt&&(t+="|ub:",t+=e.endAt.inclusive?"a:":"b:",t+=e.endAt.position.map(n=>Es(n)).join(",")),e.de=t}return e.de}function C1(r,e){if(r.limit!==e.limit||r.orderBy.length!==e.orderBy.length)return!1;for(let t=0;t<r.orderBy.length;t++)if(!n3(r.orderBy[t],e.orderBy[t]))return!1;if(r.filters.length!==e.filters.length)return!1;for(let t=0;t<r.filters.length;t++)if(!V6(r.filters[t],e.filters[t]))return!1;return r.collectionGroup===e.collectionGroup&&!!r.path.isEqual(e.path)&&!!Hd(r.startAt,e.startAt)&&Hd(r.endAt,e.endAt)}function nn(r){return!!r.isCorePipeline}function N1(r){return!!r.path&&$.isDocumentKey(r.path)&&r.collectionGroup===null&&r.filters.length===0}function xa(r,e){return r.filters.filter(t=>t instanceof ce&&t.field.isEqual(e))}function Kd(r,e,t){let n=Kt,s=!0;for(const i of xa(r,e)){let o=Kt,u=!0;switch(i.op){case"<":case"<=":o=q4(i.value);break;case"==":case"in":case">=":o=i.value;break;case">":o=i.value,u=!1;break;case"!=":case"not-in":o=Kt}Ud({value:n,inclusive:s},{value:o,inclusive:u})<0&&(n=o,s=u)}if(t!==null){for(let i=0;i<r.orderBy.length;++i)if(r.orderBy[i].field.isEqual(e)){const o=t.position[i];Ud({value:n,inclusive:s},{value:o,inclusive:t.inclusive})<0&&(n=o,s=t.inclusive);break}}return{value:n,inclusive:s}}function Wd(r,e,t){let n=Fn,s=!0;for(const i of xa(r,e)){let o=Fn,u=!0;switch(i.op){case">=":case">":o=$4(i.value),u=!1;break;case"==":case"in":case"<=":o=i.value;break;case"<":o=i.value,u=!1;break;case"!=":case"not-in":o=Fn}Bd({value:n,inclusive:s},{value:o,inclusive:u})>0&&(n=o,s=u)}if(t!==null){for(let i=0;i<r.orderBy.length;++i)if(r.orderBy[i].field.isEqual(e)){const o=t.position[i];Bd({value:n,inclusive:s},{value:o,inclusive:t.inclusive})>0&&(n=o,s=t.inclusive);break}}return{value:n,inclusive:s}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Us{constructor(e,t=null,n=[],s=[],i=null,o="F",u=null,c=null){this.path=e,this.collectionGroup=t,this.explicitOrderBy=n,this.filters=s,this.limit=i,this.limitType=o,this.startAt=u,this.endAt=c,this.fe=null,this.me=null,this.pe=null,this.startAt,this.endAt}}function L6(r,e,t,n,s,i,o,u){return new Us(r,e,t,n,s,i,o,u)}function yo(r){return new Us(r)}function Qd(r){return r.filters.length===0&&r.limit===null&&r.startAt==null&&r.endAt==null&&(r.explicitOrderBy.length===0||r.explicitOrderBy.length===1&&r.explicitOrderBy[0].field.isKeyField())}function s3(r){return $.isDocumentKey(r.path)&&r.collectionGroup===null&&r.filters.length===0}function M6(r){return r.collectionGroup!==null}function Di(r){const e=H(r);if(e.fe===null){e.fe=[];const t=new Set;for(const i of e.explicitOrderBy)e.fe.push(i),t.add(i.field.canonicalString());const n=e.explicitOrderBy.length>0?e.explicitOrderBy[e.explicitOrderBy.length-1].dir:"asc";(function(o){let u=new ge(ve.comparator);return o.filters.forEach(c=>{c.getFlattenedFilters().forEach(l=>{l.isInequality()&&(u=u.add(l.field))})}),u})(e).forEach(i=>{t.has(i.canonicalString())||i.isKeyField()||e.fe.push(new Zi(i,n))}),t.has(ve.keyField().canonicalString())||e.fe.push(new Zi(ve.keyField(),n))}return e.fe}function wt(r){const e=H(r);return e.me||(e.me=i3(e,Di(r))),e.me}function i3(r,e){if(r.limitType==="F")return qc(r.path,r.collectionGroup,e,r.filters,r.limit,r.startAt,r.endAt);{e=e.map(s=>{const i=s.dir==="desc"?"asc":"desc";return new Zi(s.field,i)});const t=r.endAt?new As(r.endAt.position,r.endAt.inclusive):null,n=r.startAt?new As(r.startAt.position,r.startAt.inclusive):null;return qc(r.path,r.collectionGroup,e,r.filters,r.limit,t,n)}}function $c(r,e){const t=r.filters.concat([e]);return new Us(r.path,r.collectionGroup,r.explicitOrderBy.slice(),t,r.limit,r.limitType,r.startAt,r.endAt)}function o3(r,e){const t=r.explicitOrderBy.concat([e]);return new Us(r.path,r.collectionGroup,t,r.filters.slice(),r.limit,r.limitType,r.startAt,r.endAt)}function Gc(r,e,t){return new Us(r.path,r.collectionGroup,r.explicitOrderBy.slice(),r.filters.slice(),e,t,r.startAt,r.endAt)}function a3(r,e){return C1(wt(r),wt(e))&&r.limitType===e.limitType}function Oi(r){return`Query(target=${function(t){let n=t.path.canonicalString();return t.collectionGroup!==null&&(n+=" collectionGroup="+t.collectionGroup),t.filters.length>0&&(n+=`, filters: [${t.filters.map(s=>D6(s)).join(", ")}]`),go(t.limit)||(n+=", limit: "+t.limit),t.orderBy.length>0&&(n+=`, orderBy: [${t.orderBy.map(s=>function(o){return`${o.field.canonicalString()} (${o.dir})`}(s)).join(", ")}]`),t.startAt&&(n+=", startAt: ",n+=t.startAt.inclusive?"b:":"a:",n+=t.startAt.position.map(s=>Es(s)).join(",")),t.endAt&&(n+=", endAt: ",n+=t.endAt.inclusive?"a:":"b:",n+=t.endAt.position.map(s=>Es(s)).join(",")),`Target(${n})`}(wt(r))}; limitType=${r.limitType})`}function hu(r,e){return e.isFoundDocument()&&function(n,s){const i=s.key.path;return n.collectionGroup!==null?s.key.hasCollectionId(n.collectionGroup)&&n.path.isPrefixOf(i):$.isDocumentKey(n.path)?n.path.isEqual(i):n.path.isImmediateParentOf(i)}(r,e)&&function(n,s){for(const i of Di(n))if(!i.field.isKeyField()&&s.data.field(i.field)===null)return!1;return!0}(r,e)&&function(n,s){for(const i of n.filters)if(!i.matches(s))return!1;return!0}(r,e)&&function(n,s){return!(n.startAt&&!function(o,u,c){const l=zd(o,u,c);return o.inclusive?l<=0:l<0}(n.startAt,Di(n),s)||n.endAt&&!function(o,u,c){const l=zd(o,u,c);return o.inclusive?l>=0:l>0}(n.endAt,Di(n),s))}(r,e)}function V1(r){return(e,t)=>{let n=!1;for(const s of Di(r)){const i=u3(s,e,t);if(i!==0)return i;n=n||s.field.isKeyField()}return 0}}function u3(r,e,t){const n=r.field.isKeyField()?$.comparator(e.key,t.key):function(i,o,u){const c=o.data.field(i),l=u.data.field(i);return c!==null&&l!==null?st(c,l):j(42886)}(r.field,e,t);switch(r.dir){case"asc":return n;case"desc":return-1*n;default:return j(19790,{direction:r.dir})}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class c3{constructor(e,t){this.count=e,this.unchangedNames=t}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */var Le,le;function F6(r){switch(r){case D.OK:return j(64938);case D.CANCELLED:case D.UNKNOWN:case D.DEADLINE_EXCEEDED:case D.RESOURCE_EXHAUSTED:case D.INTERNAL:case D.UNAVAILABLE:case D.UNAUTHENTICATED:return!1;case D.INVALID_ARGUMENT:case D.NOT_FOUND:case D.ALREADY_EXISTS:case D.PERMISSION_DENIED:case D.FAILED_PRECONDITION:case D.ABORTED:case D.OUT_OF_RANGE:case D.UNIMPLEMENTED:case D.DATA_LOSS:return!0;default:return j(15467,{code:r})}}function U6(r){if(r===void 0)return ke("GRPC error has no .code"),D.UNKNOWN;switch(r){case Le.OK:return D.OK;case Le.CANCELLED:return D.CANCELLED;case Le.UNKNOWN:return D.UNKNOWN;case Le.DEADLINE_EXCEEDED:return D.DEADLINE_EXCEEDED;case Le.RESOURCE_EXHAUSTED:return D.RESOURCE_EXHAUSTED;case Le.INTERNAL:return D.INTERNAL;case Le.UNAVAILABLE:return D.UNAVAILABLE;case Le.UNAUTHENTICATED:return D.UNAUTHENTICATED;case Le.INVALID_ARGUMENT:return D.INVALID_ARGUMENT;case Le.NOT_FOUND:return D.NOT_FOUND;case Le.ALREADY_EXISTS:return D.ALREADY_EXISTS;case Le.PERMISSION_DENIED:return D.PERMISSION_DENIED;case Le.FAILED_PRECONDITION:return D.FAILED_PRECONDITION;case Le.ABORTED:return D.ABORTED;case Le.OUT_OF_RANGE:return D.OUT_OF_RANGE;case Le.UNIMPLEMENTED:return D.UNIMPLEMENTED;case Le.DATA_LOSS:return D.DATA_LOSS;default:return j(39323,{code:r})}}(le=Le||(Le={}))[le.OK=0]="OK",le[le.CANCELLED=1]="CANCELLED",le[le.UNKNOWN=2]="UNKNOWN",le[le.INVALID_ARGUMENT=3]="INVALID_ARGUMENT",le[le.DEADLINE_EXCEEDED=4]="DEADLINE_EXCEEDED",le[le.NOT_FOUND=5]="NOT_FOUND",le[le.ALREADY_EXISTS=6]="ALREADY_EXISTS",le[le.PERMISSION_DENIED=7]="PERMISSION_DENIED",le[le.UNAUTHENTICATED=16]="UNAUTHENTICATED",le[le.RESOURCE_EXHAUSTED=8]="RESOURCE_EXHAUSTED",le[le.FAILED_PRECONDITION=9]="FAILED_PRECONDITION",le[le.ABORTED=10]="ABORTED",le[le.OUT_OF_RANGE=11]="OUT_OF_RANGE",le[le.UNIMPLEMENTED=12]="UNIMPLEMENTED",le[le.INTERNAL=13]="INTERNAL",le[le.UNAVAILABLE=14]="UNAVAILABLE",le[le.DATA_LOSS=15]="DATA_LOSS";/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class En{constructor(e,t){this.mapKeyFn=e,this.equalsFn=t,this.inner={},this.innerSize=0}get(e){const t=this.mapKeyFn(e),n=this.inner[t];if(n!==void 0){for(const[s,i]of n)if(this.equalsFn(s,e))return i}}has(e){return this.get(e)!==void 0}set(e,t){const n=this.mapKeyFn(e),s=this.inner[n];if(s===void 0)return this.inner[n]=[[e,t]],void this.innerSize++;for(let i=0;i<s.length;i++)if(this.equalsFn(s[i][0],e))return void(s[i]=[e,t]);s.push([e,t]),this.innerSize++}delete(e){const t=this.mapKeyFn(e),n=this.inner[t];if(n===void 0)return!1;for(let s=0;s<n.length;s++)if(this.equalsFn(n[s][0],e))return n.length===1?delete this.inner[t]:n.splice(s,1),this.innerSize--,!0;return!1}forEach(e){rr(this.inner,(t,n)=>{for(const[s,i]of n)e(s,i)})}isEmpty(){return f6(this.inner)}size(){return this.innerSize}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const l3=new Ie($.comparator);function Be(){return l3}const B6=new Ie($.comparator);function mr(...r){let e=B6;for(const t of r)e=e.insert(t.key,t);return e}function q6(r){let e=B6;return r.forEach((t,n)=>e=e.insert(t,n.overlayedDocument)),e}function vt(){return ki()}function $6(){return ki()}function ki(){return new En(r=>r.toString(),(r,e)=>r.isEqual(e))}const h3=new Ie($.comparator),d3=new ge($.comparator);function se(...r){let e=d3;for(const t of r)e=e.add(t);return e}const f3=new ge(Z);function x1(){return f3}/**
 * @license
 * Copyright 2023 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function p3(){return new TextEncoder}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const g3=new qn([4294967295,4294967295],0);function Yd(r){const e=p3().encode(r),t=new q2;return t.update(e),new Uint8Array(t.digest())}function Xd(r){const e=new DataView(r.buffer),t=e.getUint32(0,!0),n=e.getUint32(4,!0),s=e.getUint32(8,!0),i=e.getUint32(12,!0);return[new qn([t,n],0),new qn([s,i],0)]}class D1{constructor(e,t,n){if(this.bitmap=e,this.padding=t,this.hashCount=n,t<0||t>=8)throw new Ti(`Invalid padding: ${t}`);if(n<0)throw new Ti(`Invalid hash count: ${n}`);if(e.length>0&&this.hashCount===0)throw new Ti(`Invalid hash count: ${n}`);if(e.length===0&&t!==0)throw new Ti(`Invalid padding when bitmap length is 0: ${t}`);this.ge=8*e.length-t,this.ye=qn.fromNumber(this.ge)}we(e,t,n){let s=e.add(t.multiply(qn.fromNumber(n)));return s.compare(g3)===1&&(s=new qn([s.getBits(0),s.getBits(1)],0)),s.modulo(this.ye).toNumber()}be(e){return!!(this.bitmap[Math.floor(e/8)]&1<<e%8)}mightContain(e){if(this.ge===0)return!1;const t=Yd(e),[n,s]=Xd(t);for(let i=0;i<this.hashCount;i++){const o=this.we(n,s,i);if(!this.be(o))return!1}return!0}static create(e,t,n){const s=e%8==0?0:8-e%8,i=new Uint8Array(Math.ceil(e/8)),o=new D1(i,s,t);return n.forEach(u=>o.insert(u)),o}insert(e){if(this.ge===0)return;const t=Yd(e),[n,s]=Xd(t);for(let i=0;i<this.hashCount;i++){const o=this.we(n,s,i);this.ve(o)}}ve(e){const t=Math.floor(e/8),n=e%8;this.bitmap[t]|=1<<n}}class Ti extends Error{constructor(){super(...arguments),this.name="BloomFilterError"}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Bs{constructor(e,t,n,s,i,o){this.snapshotVersion=e,this.targetChanges=t,this.targetMismatches=n,this.documentUpdates=s,this.augmentedDocumentUpdates=i,this.resolvedLimboDocuments=o}static createSynthesizedRemoteEventForCurrentChange(e,t,n){const s=new Map;return s.set(e,Eo.createSynthesizedTargetChangeForCurrentChange(e,t,n)),new Bs(K.min(),s,new Ie(Z),Be(),Be(),se())}}class Eo{constructor(e,t,n,s,i){this.resumeToken=e,this.current=t,this.addedDocuments=n,this.modifiedDocuments=s,this.removedDocuments=i}static createSynthesizedTargetChangeForCurrentChange(e,t,n){return new Eo(n,t,se(),se(),se())}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class da{constructor(e,t,n,s){this.Se=e,this.removedTargetIds=t,this.key=n,this.De=s}}class G6{constructor(e,t){this.targetId=e,this.xe=t}}class j6{constructor(e,t,n=Ce.EMPTY_BYTE_STRING,s=null){this.state=e,this.targetIds=t,this.resumeToken=n,this.cause=s}}class Jd{constructor(e){this.targetId=e,this.Ce=0,this.Fe=Zd(),this.Oe=Ce.EMPTY_BYTE_STRING,this.Me=!1,this.Ne=!0}get current(){return this.Me}get resumeToken(){return this.Oe}get Le(){return this.Ce!==0}get Be(){return this.Ne}Ue(e){e.approximateByteSize()>0&&(this.Ne=!0,this.Oe=e)}ke(){let e=se(),t=se(),n=se();return this.Fe.forEach((s,i)=>{switch(i){case 0:e=e.add(s);break;case 2:t=t.add(s);break;case 1:n=n.add(s);break;default:j(38017,{changeType:i})}}),new Eo(this.Oe,this.Me,e,t,n)}qe(){this.Ne=!1,this.Fe=Zd()}$e(e,t){this.Ne=!0,this.Fe=this.Fe.insert(e,t)}Ke(e){this.Ne=!0,this.Fe=this.Fe.remove(e)}We(){this.Ce+=1}Qe(){this.Ce-=1,U(this.Ce>=0,3241,{Ce:this.Ce,targetId:this.targetId})}Ge(){this.Ne=!0,this.Me=!0}}const gi="WatchChangeAggregator";class m3{constructor(e){this.ze=e,this.je=new Map,this.He=Be(),this.Je=Xo(),this.Ye=Be(),this.Ze=Xo(),this.Xe=new Ie(Z)}et(e){for(const t of e.Se)e.De&&e.De.isFoundDocument()?this.tt(t,e.De):this.nt(t,e.key,e.De);for(const t of e.removedTargetIds)this.nt(t,e.key,e.De)}rt(e){this.forEachTarget(e,t=>{const n=this.je.get(t);if(n)switch(e.state){case 0:this.it(t)&&n.Ue(e.resumeToken);break;case 1:n.Qe(),n.Le||n.qe(),n.Ue(e.resumeToken);break;case 2:n.Qe(),n.Le||this.removeTarget(t);break;case 3:this.it(t)&&(n.Ge(),n.Ue(e.resumeToken));break;case 4:this.it(t)&&(this.st(t),n.Ue(e.resumeToken));break;default:j(56790,{state:e.state})}else L(gi,`handleTargetChange received targetChange for untracked target ID (${t}) with state (${e.state})`)})}forEachTarget(e,t){e.targetIds.length>0?e.targetIds.forEach(t):this.je.forEach((n,s)=>{this.it(s)&&t(s)})}_t(e){var t;return nn(e)?e.getPipelineSourceType()==="documents"&&((t=e.getPipelineDocuments())==null?void 0:t.length)===1:N1(e)}ot(e){const t=e.targetId,n=e.xe.count,s=this.ut(t);if(s){const i=s.target;if(this._t(i))if(n===0){const o=new $(nn(i)?ae.fromString(i.getPipelineDocuments()[0]):i.path);this.nt(t,o,Re.newNoDocument(o,K.min()))}else U(n===1,20013,"Single document existence filter with count: "+n);else{const o=this.ct(t);if(o!==n){const u=this.lt(e),c=u?this.Et(u,e,o):1;if(c!==0){this.st(t);const l=c===2?"TargetPurposeExistenceFilterMismatchBloom":"TargetPurposeExistenceFilterMismatch";this.Xe=this.Xe.insert(t,l)}}}}}lt(e){const t=e.xe.unchangedNames;if(!t||!t.bits)return null;const{bits:{bitmap:n="",padding:s=0},hashCount:i=0}=t;let o,u;try{o=dn(n).toUint8Array()}catch(c){if(c instanceof p6)return Lt("Decoding the base64 bloom filter in existence filter failed ("+c.message+"); ignoring the bloom filter and falling back to full re-query."),null;throw c}try{u=new D1(o,s,i)}catch(c){return Lt(c instanceof Ti?"BloomFilter error: ":"Applying bloom filter failed: ",c),null}return u.ge===0?null:u}Et(e,t,n){return t.xe.count===n-this.Pt(e,t.targetId)?0:2}Pt(e,t){const n=this.ze.getRemoteKeysForTarget(t);let s=0;return n.forEach(i=>{const o=this.ze.Tt(),u=`projects/${o.projectId}/databases/${o.database}/documents/${i.path.canonicalString()}`;e.mightContain(u)||(this.nt(t,i,null),s++)}),s}Rt(e){const t=new Map;this.je.forEach((i,o)=>{const u=this.ut(o);if(u){if(i.current&&this._t(u.target)){const c=nn(u.target)?ae.fromString(u.target.getPipelineDocuments()[0]):u.target.path,l=new $(c);this.It(l).has(o)||this.At(o,l)||this.nt(o,l,Re.newNoDocument(l,e))}i.Be&&(t.set(o,i.ke()),i.qe())}});let n=se();this.Ze.forEach((i,o)=>{let u=!0;o.forEachWhile(c=>{const l=this.ut(c);return!l||l.purpose==="TargetPurposeLimboResolution"||(u=!1,!1)}),u&&(n=n.add(i))}),this.He.forEach((i,o)=>o.setReadTime(e)),this.Ye.forEach((i,o)=>o.setReadTime(e));const s=new Bs(e,t,this.Xe,this.He,this.Ye,n);return this.He=Be(),this.Je=Xo(),this.Ye=Be(),this.Ze=Xo(),this.Xe=new Ie(Z),s}tt(e,t){const n=this.je.get(e);if(!n||!this.it(e))return void L(gi,`addDocumentToTarget received document for unknown inactive target (${e})`);const s=this.At(e,t.key)?2:0;n.$e(t.key,s),nn(this.ut(e).target)&&this.ut(e).target.getPipelineFlavor()!=="exact"?this.Ye=this.Ye.insert(t.key,t):this.He=this.He.insert(t.key,t),this.Je=this.Je.insert(t.key,this.It(t.key).add(e)),this.Ze=this.Ze.insert(t.key,this.Vt(t.key).add(e))}nt(e,t,n){const s=this.je.get(e);s&&this.it(e)?(this.At(e,t)?s.$e(t,1):s.Ke(t),this.Ze=this.Ze.insert(t,this.Vt(t).delete(e)),this.Ze=this.Ze.insert(t,this.Vt(t).add(e)),n&&(nn(this.ut(e).target)&&this.ut(e).target.getPipelineFlavor()!=="exact"?this.Ye=this.Ye.insert(t,n):this.He=this.He.insert(t,n))):L(gi,`removeDocumentFromTarget received document for unknown or inactive target (${e})`)}removeTarget(e){this.je.delete(e)}ct(e){const t=this.je.get(e);if(!t)return 0;const n=t.ke();return this.ze.getRemoteKeysForTarget(e).size+n.addedDocuments.size-n.removedDocuments.size}We(e){let t=this.je.get(e);t||(L(gi,`recordPendingTargetRequest set up tracking for target ID ${e}`),t=new Jd(e),this.je.set(e,t)),t.We()}Vt(e){let t=this.Ze.get(e);return t||(t=new ge(Z),this.Ze=this.Ze.insert(e,t)),t}It(e){let t=this.Je.get(e);return t||(t=new ge(Z),this.Je=this.Je.insert(e,t)),t}it(e){const t=this.ut(e)!==null;return t||L(gi,"Detected inactive target",e),t}ut(e){const t=this.je.get(e);return t===void 0||t.Le?null:this.ze.dt(e)}st(e){this.je.set(e,new Jd(e)),this.ze.getRemoteKeysForTarget(e).forEach(t=>{this.nt(e,t,null)})}At(e,t){return this.ze.getRemoteKeysForTarget(e).has(t)}}function Xo(){return new Ie($.comparator)}function Zd(){return new Ie($.comparator)}const _3={asc:"ASCENDING",desc:"DESCENDING"},y3={"<":"LESS_THAN","<=":"LESS_THAN_OR_EQUAL",">":"GREATER_THAN",">=":"GREATER_THAN_OR_EQUAL","==":"EQUAL","!=":"NOT_EQUAL","array-contains":"ARRAY_CONTAINS",in:"IN","not-in":"NOT_IN","array-contains-any":"ARRAY_CONTAINS_ANY"},E3={and:"AND",or:"OR"};class I3{constructor(e,t){this.databaseId=e,this.useProto3Json=t}}function jc(r,e){return r.useProto3Json||go(e)?e:{value:e}}function Rs(r,e){return r.useProto3Json?`${new Date(1e3*e.seconds).toISOString().replace(/\.\d*/,"").replace("Z","")}.${("000000000"+e.nanoseconds).slice(-9)}Z`:{seconds:""+e.seconds,nanos:e.nanoseconds}}function O1(r){const e=hn(r);return new me(e.seconds,e.nanos)}function z6(r,e){return r.useProto3Json?e.toBase64():e.toUint8Array()}function fa(r,e){return Rs(r,e.toTimestamp())}function Ke(r){return U(!!r,49232),K.fromTimestamp(O1(r))}function k1(r,e){return zc(r,e).canonicalString()}function zc(r,e){const t=function(s){return new ae(["projects",s.projectId,"databases",s.database])}(r).child("documents");return e===void 0?t:t.child(e)}function H6(r){const e=ae.fromString(r);return U(np(e),10190,{key:e.toString()}),e}function Ss(r,e){return k1(r.databaseId,e.path)}function an(r,e){const t=H6(e);if(t.get(1)!==r.databaseId.projectId)throw new F(D.INVALID_ARGUMENT,"Tried to deserialize key from different project: "+t.get(1)+" vs "+r.databaseId.projectId);if(t.get(3)!==r.databaseId.database)throw new F(D.INVALID_ARGUMENT,"Tried to deserialize key from different database: "+t.get(3)+" vs "+r.databaseId.database);return new $(Q6(t))}function K6(r,e){return k1(r.databaseId,e)}function W6(r){const e=H6(r);return e.length===4?ae.emptyPath():Q6(e)}function Hc(r){return new ae(["projects",r.databaseId.projectId,"databases",r.databaseId.database]).canonicalString()}function Q6(r){return U(r.length>4&&r.get(4)==="documents",29091,{key:r.toString()}),r.popFirst(5)}function ef(r,e,t){return{name:Ss(r,e),fields:t.value.mapValue.fields}}function w3(r,e,t){const n=an(r,e.name),s=Ke(e.updateTime),i=e.createTime?Ke(e.createTime):K.min(),o=new ze({mapValue:{fields:e.fields}}),u=Re.newFoundDocument(n,s,i,o);return t&&u.setHasCommittedMutations(),t?u.setHasCommittedMutations():u}function T3(r,e){return"found"in e?function(n,s){U(!!s.found,43571),s.found.name,s.found.updateTime;const i=an(n,s.found.name),o=Ke(s.found.updateTime),u=s.found.createTime?Ke(s.found.createTime):K.min(),c=new ze({mapValue:{fields:s.found.fields}});return Re.newFoundDocument(i,o,u,c)}(r,e):"missing"in e?function(n,s){U(!!s.missing,3894),U(!!s.readTime,22933);const i=an(n,s.missing),o=Ke(s.readTime);return Re.newNoDocument(i,o)}(r,e):j(7234,{result:e})}function A3(r,e){let t;if("targetChange"in e){e.targetChange;const n=function(l){return l==="NO_CHANGE"?0:l==="ADD"?1:l==="REMOVE"?2:l==="CURRENT"?3:l==="RESET"?4:j(39313,{state:l})}(e.targetChange.targetChangeType||"NO_CHANGE"),s=e.targetChange.targetIds||[],i=function(l,d){return l.useProto3Json?(U(d===void 0||typeof d=="string",58123),Ce.fromBase64String(d||"")):(U(d===void 0||d instanceof Buffer||d instanceof Uint8Array,16193),Ce.fromUint8Array(d||new Uint8Array))}(r,e.targetChange.resumeToken),o=e.targetChange.cause,u=o&&function(l){const d=l.code===void 0?D.UNKNOWN:U6(l.code);return new F(d,l.message||"")}(o);t=new j6(n,s,i,u||null)}else if("documentChange"in e){e.documentChange;const n=e.documentChange;n.document,n.document.name,n.document.updateTime;const s=an(r,n.document.name),i=Ke(n.document.updateTime),o=n.document.createTime?Ke(n.document.createTime):K.min(),u=new ze({mapValue:{fields:n.document.fields}}),c=Re.newFoundDocument(s,i,o,u),l=n.targetIds||[],d=n.removedTargetIds||[];t=new da(l,d,c.key,c)}else if("documentDelete"in e){e.documentDelete;const n=e.documentDelete;n.document;const s=an(r,n.document),i=n.readTime?Ke(n.readTime):K.min(),o=Re.newNoDocument(s,i),u=n.removedTargetIds||[];t=new da([],u,o.key,o)}else if("documentRemove"in e){e.documentRemove;const n=e.documentRemove;n.document;const s=an(r,n.document),i=n.removedTargetIds||[];t=new da([],i,s,null)}else{if(!("filter"in e))return j(11601,{ft:e});{e.filter;const n=e.filter;n.targetId;const{count:s=0,unchangedNames:i}=n,o=new c3(s,i),u=n.targetId;t=new G6(u,o)}}return t}function eo(r,e){let t;if(e instanceof Ms)t={update:ef(r,e.key,e.value)};else if(e instanceof Fs)t={delete:Ss(r,e.key)};else if(e instanceof yn)t={update:ef(r,e.key,e.data),updateMask:C3(e.fieldMask)};else{if(!(e instanceof P1))return j(16599,{gt:e.type});t={verify:Ss(r,e.key)}}return e.fieldTransforms.length>0&&(t.updateTransforms=e.fieldTransforms.map(n=>function(i,o){const u=o.transform;if(u instanceof Yi)return{fieldPath:o.field.canonicalString(),setToServerValue:"REQUEST_TIME"};if(u instanceof Is)return{fieldPath:o.field.canonicalString(),appendMissingElements:{values:u.elements}};if(u instanceof ws)return{fieldPath:o.field.canonicalString(),removeAllFromArray:{values:u.elements}};if(u instanceof Ts)return{fieldPath:o.field.canonicalString(),increment:u.Re};if(u instanceof Xi)return{fieldPath:o.field.canonicalString(),minimum:u.Re};if(u instanceof Ji)return{fieldPath:o.field.canonicalString(),maximum:u.Re};throw j(20930,{transform:o.transform})}(0,n))),e.precondition.isNone||(t.currentDocument=function(s,i){return i.updateTime!==void 0?{updateTime:fa(s,i.updateTime)}:i.exists!==void 0?{exists:i.exists}:j(27497)}(r,e.precondition)),t}function Kc(r,e){const t=e.currentDocument?function(i){return i.updateTime!==void 0?Se.updateTime(Ke(i.updateTime)):i.exists!==void 0?Se.exists(i.exists):Se.none()}(e.currentDocument):Se.none(),n=e.updateTransforms?e.updateTransforms.map(s=>function(o,u){let c=null;if("setToServerValue"in u)U(u.setToServerValue==="REQUEST_TIME",16630,{proto:u}),c=new Yi;else if("appendMissingElements"in u){const d=u.appendMissingElements.values||[];c=new Is(d)}else if("removeAllFromArray"in u){const d=u.removeAllFromArray.values||[];c=new ws(d)}else"increment"in u?c=new Ts(o,u.increment):"minimum"in u?c=new Xi(o,u.minimum):"maximum"in u?c=new Ji(o,u.maximum):j(16584,{proto:u});const l=ve.fromServerFormat(u.fieldPath);return new z4(l,c)}(r,s)):[];if(e.update){e.update.name;const s=an(r,e.update.name),i=new ze({mapValue:{fields:e.update.fields}});if(e.updateMask){const o=function(c){const l=c.fieldPaths||[];return new dt(l.map(d=>ve.fromServerFormat(d)))}(e.updateMask);return new yn(s,i,o,t,n)}return new Ms(s,i,t,n)}if(e.delete){const s=an(r,e.delete);return new Fs(s,t)}if(e.verify){const s=an(r,e.verify);return new P1(s,t)}return j(1463,{proto:e})}function v3(r,e){return r&&r.length>0?(U(e!==void 0,14353),r.map(t=>function(s,i){let o=s.updateTime?Ke(s.updateTime):Ke(i);return o.isEqual(K.min())&&(o=Ke(i)),new K4(o,s.transformResults||[])}(t,e))):[]}function Y6(r,e){return{documents:[K6(r,e.path)]}}function X6(r,e){const t={structuredQuery:{}},n=e.path;let s;e.collectionGroup!==null?(s=n,t.structuredQuery.from=[{collectionId:e.collectionGroup,allDescendants:!0}]):(s=n.popLast(),t.structuredQuery.from=[{collectionId:n.lastSegment()}]),t.parent=K6(r,s);const i=function(l){if(l.length!==0)return tp(_e.create(l,"and"))}(e.filters);i&&(t.structuredQuery.where=i);const o=function(l){if(l.length!==0)return l.map(d=>function(y){return{field:ns(y.field),direction:S3(y.dir)}}(d))}(e.orderBy);o&&(t.structuredQuery.orderBy=o);const u=jc(r,e.limit);return u!==null&&(t.structuredQuery.limit=u),e.startAt&&(t.structuredQuery.startAt=function(l){return{before:l.inclusive,values:l.position}}(e.startAt)),e.endAt&&(t.structuredQuery.endAt=function(l){return{before:!l.inclusive,values:l.position}}(e.endAt)),{yt:t,parent:s}}function J6(r){let e=W6(r.parent);const t=r.structuredQuery,n=t.from?t.from.length:0;let s=null;if(n>0){U(n===1,65062);const d=t.from[0];d.allDescendants?s=d.collectionId:e=e.child(d.collectionId)}let i=[];t.where&&(i=function(g){const y=ep(g);return y instanceof _e&&b1(y)?y.getFilters():[y]}(t.where));let o=[];t.orderBy&&(o=function(g){return g.map(y=>function(C){return new Zi(rs(C.field),function(q){switch(q){case"ASCENDING":return"asc";case"DESCENDING":return"desc";default:return}}(C.direction))}(y))}(t.orderBy));let u=null;t.limit&&(u=function(g){let y;return y=typeof g=="object"?g.value:g,go(y)?null:y}(t.limit));let c=null;t.startAt&&(c=function(g){const y=!!g.before,R=g.values||[];return new As(R,y)}(t.startAt));let l=null;return t.endAt&&(l=function(g){const y=!g.before,R=g.values||[];return new As(R,y)}(t.endAt)),L6(e,s,o,i,u,"F",c,l)}function R3(r,e){const t=function(s){switch(s){case"TargetPurposeListen":return null;case"TargetPurposeExistenceFilterMismatch":return"existence-filter-mismatch";case"TargetPurposeExistenceFilterMismatchBloom":return"existence-filter-mismatch-bloom";case"TargetPurposeLimboResolution":return"limbo-document";default:return j(28987,{purpose:s})}}(e.purpose);return t==null?null:{"goog-listen-tags":t}}function Z6(r,e){return{structuredPipeline:{pipeline:{stages:e.stages.map(t=>t._toProto(r))}}}}function ep(r){return r.unaryFilter!==void 0?function(t){switch(t.unaryFilter.op){case"IS_NAN":const n=rs(t.unaryFilter.field);return ce.create(n,"==",{doubleValue:NaN});case"IS_NULL":const s=rs(t.unaryFilter.field);return ce.create(s,"==",{nullValue:"NULL_VALUE"});case"IS_NOT_NAN":const i=rs(t.unaryFilter.field);return ce.create(i,"!=",{doubleValue:NaN});case"IS_NOT_NULL":const o=rs(t.unaryFilter.field);return ce.create(o,"!=",{nullValue:"NULL_VALUE"});case"OPERATOR_UNSPECIFIED":return j(61313);default:return j(60726)}}(r):r.fieldFilter!==void 0?function(t){return ce.create(rs(t.fieldFilter.field),function(s){switch(s){case"EQUAL":return"==";case"NOT_EQUAL":return"!=";case"GREATER_THAN":return">";case"GREATER_THAN_OR_EQUAL":return">=";case"LESS_THAN":return"<";case"LESS_THAN_OR_EQUAL":return"<=";case"ARRAY_CONTAINS":return"array-contains";case"IN":return"in";case"NOT_IN":return"not-in";case"ARRAY_CONTAINS_ANY":return"array-contains-any";case"OPERATOR_UNSPECIFIED":return j(58110);default:return j(50506)}}(t.fieldFilter.op),t.fieldFilter.value)}(r):r.compositeFilter!==void 0?function(t){return _e.create(t.compositeFilter.filters.map(n=>ep(n)),function(s){switch(s){case"AND":return"and";case"OR":return"or";default:return j(1026)}}(t.compositeFilter.op))}(r):j(30097,{filter:r})}function S3(r){return _3[r]}function P3(r){return y3[r]}function b3(r){return E3[r]}function ns(r){return{fieldPath:r.canonicalString()}}function rs(r){return ve.fromServerFormat(r.fieldPath)}function tp(r){return r instanceof ce?function(t){if(t.op==="=="){if(gt(t.value))return{unaryFilter:{field:ns(t.field),op:"IS_NAN"}};if(It(t.value))return{unaryFilter:{field:ns(t.field),op:"IS_NULL"}}}else if(t.op==="!="){if(gt(t.value))return{unaryFilter:{field:ns(t.field),op:"IS_NOT_NAN"}};if(It(t.value))return{unaryFilter:{field:ns(t.field),op:"IS_NOT_NULL"}}}return{fieldFilter:{field:ns(t.field),op:P3(t.op),value:t.value}}}(r):r instanceof _e?function(t){const n=t.getFilters().map(s=>tp(s));return n.length===1?n[0]:{compositeFilter:{op:b3(t.op),filters:n}}}(r):j(54877,{filter:r})}function C3(r){const e=[];return r.fields.forEach(t=>e.push(t.canonicalString())),{fieldPaths:e}}function np(r){return r.length>=4&&r.get(0)==="projects"&&r.get(2)==="databases"}function rp(r){return!!r&&typeof r._toProto=="function"&&r._protoValueType==="ProtoValue"}function to(r,e){const t={fields:{}};return e.forEach((n,s)=>{if(typeof s!="string")throw new Error(`Cannot encode map with non-string key: ${s}`);t.fields[s]=n._toProto(r)}),{mapValue:t}}function sp(r){return{stringValue:r}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function du(r){return new I3(r,!0)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Et{constructor(e){this._byteString=e}static fromBase64String(e){try{return new Et(Ce.fromBase64String(e))}catch(t){throw new F(D.INVALID_ARGUMENT,"Failed to construct data from Base64 string: "+t)}}static fromUint8Array(e){return new Et(Ce.fromUint8Array(e))}toBase64(){return this._byteString.toBase64()}toUint8Array(){return this._byteString.toUint8Array()}toString(){return"Bytes(base64: "+this.toBase64()+")"}isEqual(e){return this._byteString.isEqual(e._byteString)}toJSON(){return{type:Et._jsonSchemaVersion,bytes:this.toBase64()}}static fromJSON(e){if(po(e,Et._jsonSchema))return Et.fromBase64String(e.bytes)}}Et._jsonSchemaVersion="firestore/bytes/1.0",Et._jsonSchema={type:Me("string",Et._jsonSchemaVersion),bytes:Me("string")};/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class qs{constructor(...e){for(let t=0;t<e.length;++t)if(e[t].length===0)throw new F(D.INVALID_ARGUMENT,"Invalid field name at argument $(i + 1). Field names must not be empty.");this._internalPath=new ve(e)}isEqual(e){return this._internalPath.isEqual(e._internalPath)}}function N3(){return new qs($t)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class L1{constructor(e){this._methodName=e}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Wt{constructor(e,t){if(!isFinite(e)||e<-90||e>90)throw new F(D.INVALID_ARGUMENT,"Latitude must be a number between -90 and 90, but was: "+e);if(!isFinite(t)||t<-180||t>180)throw new F(D.INVALID_ARGUMENT,"Longitude must be a number between -180 and 180, but was: "+t);this._lat=e,this._long=t}get latitude(){return this._lat}get longitude(){return this._long}isEqual(e){return this._lat===e._lat&&this._long===e._long}_compareTo(e){return Z(this._lat,e._lat)||Z(this._long,e._long)}toJSON(){return{latitude:this._lat,longitude:this._long,type:Wt._jsonSchemaVersion}}static fromJSON(e){if(po(e,Wt._jsonSchema))return new Wt(e.latitude,e.longitude)}}function ip(r){const e={};return r.timeoutSeconds!==void 0&&(e.timeoutSeconds=r.timeoutSeconds),e}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */Wt._jsonSchemaVersion="firestore/geoPoint/1.0",Wt._jsonSchema={type:Me("string",Wt._jsonSchemaVersion),latitude:Me("number"),longitude:Me("number")};class V3{bt(e){}shutdown(){}}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const tf="ConnectivityMonitor";class nf{constructor(){this.vt=()=>this.St(),this.Dt=()=>this.xt(),this.Ct=[],this.Ft()}bt(e){this.Ct.push(e)}shutdown(){window.removeEventListener("online",this.vt),window.removeEventListener("offline",this.Dt)}Ft(){window.addEventListener("online",this.vt),window.addEventListener("offline",this.Dt)}St(){L(tf,"Network connectivity changed: AVAILABLE");for(const e of this.Ct)e(0)}xt(){L(tf,"Network connectivity changed: UNAVAILABLE");for(const e of this.Ct)e(1)}static C(){return typeof window<"u"&&window.addEventListener!==void 0&&window.removeEventListener!==void 0}}/**
 * @license
 * Copyright 2023 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */let Jo=null;function Wc(){return Jo===null?Jo=function(){return 268435456+Math.round(2147483648*Math.random())}():Jo++,"0x"+Jo.toString(16)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const cc="RestConnection",x3={BatchGetDocuments:"batchGet",Commit:"commit",RunQuery:"runQuery",RunAggregationQuery:"runAggregationQuery",ExecutePipeline:"executePipeline"};class D3{get Ot(){return!1}constructor(e){this.databaseInfo=e,this.databaseId=e.databaseId;const t=e.ssl?"https":"http",n=encodeURIComponent(this.databaseId.projectId),s=encodeURIComponent(this.databaseId.database);this.Mt=t+"://"+e.host,this.Nt=`projects/${n}/databases/${s}`,this.Lt=this.databaseId.database===Ca?`project_id=${n}`:`project_id=${n}&database_id=${s}`}Bt(e,t,n,s,i){const o=Wc(),u=this.Ut(e,t.toUriEncodedString());L(cc,`Sending RPC '${e}' ${o}:`,u,n);const c={"google-cloud-resource-prefix":this.Nt,"x-goog-request-params":this.Lt};this.kt(c,s,i);const{host:l}=new URL(u),d=ho(l);return this.qt(e,u,c,n,d).then(g=>(L(cc,`Received RPC '${e}' ${o}: `,g),g),g=>{throw Lt(cc,`RPC '${e}' ${o} failed with error: `,g,"url: ",u,"request:",n),g})}$t(e,t,n,s,i,o){return this.Bt(e,t,n,s,i)}kt(e,t,n){e["X-Goog-Api-Client"]=function(){return"gl-js/ fire/"+Ls}(),e["Content-Type"]="text/plain",this.databaseInfo.appId&&(e["X-Firebase-GMPID"]=this.databaseInfo.appId),t&&t.headers.forEach((s,i)=>e[i]=s),n&&n.headers.forEach((s,i)=>e[i]=s)}Ut(e,t){const n=x3[e];let s=`${this.Mt}/v1/${t}:${n}`;return this.databaseInfo.apiKey&&(s=`${s}?key=${encodeURIComponent(this.databaseInfo.apiKey)}`),s}terminate(){}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class O3{constructor(e){this.Kt=e.Kt,this.Wt=e.Wt}Qt(e){this.Gt=e}zt(e){this.jt=e}Ht(e){this.Jt=e}onMessage(e){this.Yt=e}close(){this.Wt()}send(e){this.Kt(e)}Zt(){this.Gt()}Xt(){this.jt()}en(e){this.Jt(e)}tn(e){this.Yt(e)}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Xe="WebChannelConnection",mi=(r,e,t)=>{r.listen(e,n=>{try{t(n)}catch(s){setTimeout(()=>{throw s},0)}})};class as extends D3{constructor(e){super(e),this.nn=[],this.forceLongPolling=e.forceLongPolling,this.autoDetectLongPolling=e.autoDetectLongPolling,this.useFetchStreams=e.useFetchStreams,this.longPollingOptions=e.longPollingOptions}static rn(){if(!as.sn){const e=z2();mi(e,j2.STAT_EVENT,t=>{t.stat===Cc.PROXY?L(Xe,"STAT_EVENT: detected buffering proxy"):t.stat===Cc.NOPROXY&&L(Xe,"STAT_EVENT: detected no buffering proxy")}),as.sn=!0}}qt(e,t,n,s,i){const o=Wc();return new Promise((u,c)=>{const l=new $2;l.setWithCredentials(!0),l.listenOnce(G2.COMPLETE,()=>{try{switch(l.getLastErrorCode()){case oa.NO_ERROR:const g=l.getResponseJson();L(Xe,`XHR for RPC '${e}' ${o} received:`,JSON.stringify(g)),u(g);break;case oa.TIMEOUT:L(Xe,`RPC '${e}' ${o} timed out`),c(new F(D.DEADLINE_EXCEEDED,"Request time out"));break;case oa.HTTP_ERROR:const y=l.getStatus();if(L(Xe,`RPC '${e}' ${o} failed with status:`,y,"response text:",l.getResponseText()),y>0){let R=l.getResponseJson();Array.isArray(R)&&(R=R[0]);const C=R==null?void 0:R.error;if(C&&C.status&&C.message){const M=function(Q){const te=Q.toLowerCase().replace(/_/g,"-");return Object.values(D).indexOf(te)>=0?te:D.UNKNOWN}(C.status);c(new F(M,C.message))}else c(new F(D.UNKNOWN,"Server responded with status "+l.getStatus()))}else c(new F(D.UNAVAILABLE,"Connection failed."));break;default:j(9055,{_n:e,streamId:o,an:l.getLastErrorCode(),un:l.getLastError()})}}finally{L(Xe,`RPC '${e}' ${o} completed.`)}});const d=JSON.stringify(s);L(Xe,`RPC '${e}' ${o} sending request:`,s),l.send(t,"POST",d,n,15)})}cn(e,t,n){const s=Wc(),i=[this.Mt,"/","google.firestore.v1.Firestore","/",e,"/channel"],o=this.createWebChannelTransport(),u={httpSessionIdParam:"gsessionid",initMessageHeaders:{},messageUrlParams:{database:`projects/${this.databaseId.projectId}/databases/${this.databaseId.database}`},sendRawJson:!0,supportsCrossDomainXhr:!0,internalChannelParams:{forwardChannelRequestTimeoutMs:6e5},forceLongPolling:this.forceLongPolling,detectBufferingProxy:this.autoDetectLongPolling},c=this.longPollingOptions.timeoutSeconds;c!==void 0&&(u.longPollingTimeout=Math.round(1e3*c)),this.useFetchStreams&&(u.useFetchStreams=!0),this.kt(u.initMessageHeaders,t,n),u.encodeInitMessageHeaders=!0;const l=i.join("");L(Xe,`Creating RPC '${e}' stream ${s}: ${l}`,u);const d=o.createWebChannel(l,u);this.En(d);let g=!1,y=!1;const R=new O3({Kt:C=>{y?L(Xe,`Not sending because RPC '${e}' stream ${s} is closed:`,C):(g||(L(Xe,`Opening RPC '${e}' stream ${s} transport.`),d.open(),g=!0),L(Xe,`RPC '${e}' stream ${s} sending:`,C),d.send(C))},Wt:()=>d.close()});return mi(d,wi.EventType.OPEN,()=>{y||(L(Xe,`RPC '${e}' stream ${s} transport opened.`),R.Zt())}),mi(d,wi.EventType.CLOSE,()=>{y||(y=!0,L(Xe,`RPC '${e}' stream ${s} transport closed`),R.en(),this.hn(d))}),mi(d,wi.EventType.ERROR,C=>{y||(y=!0,Lt(Xe,`RPC '${e}' stream ${s} transport errored. Name:`,C.name,"Message:",C.message),R.en(new F(D.UNAVAILABLE,"The operation could not be completed")))}),mi(d,wi.EventType.MESSAGE,C=>{var M;if(!y){const q=C.data[0];U(!!q,16349);const Q=q,te=(Q==null?void 0:Q.error)||((M=Q[0])==null?void 0:M.error);if(te){L(Xe,`RPC '${e}' stream ${s} received error:`,te);const ne=te.status;let Te=function(A){const E=Le[A];if(E!==void 0)return U6(E)}(ne),de=te.message;ne==="NOT_FOUND"&&de.includes("database")&&de.includes("does not exist")&&de.includes(this.databaseId.database)&&Lt(`Database '${this.databaseId.database}' not found. Please check your project configuration.`),Te===void 0&&(Te=D.INTERNAL,de="Unknown error status: "+ne+" with message "+te.message),y=!0,R.en(new F(Te,de)),d.close()}else L(Xe,`RPC '${e}' stream ${s} received:`,q),R.tn(q)}}),as.rn(),setTimeout(()=>{R.Xt()},0),R}terminate(){this.nn.forEach(e=>e.close()),this.nn=[]}En(e){this.nn.push(e)}hn(e){this.nn=this.nn.filter(t=>t===e)}kt(e,t,n){super.kt(e,t,n),this.databaseInfo.apiKey&&(e["x-goog-api-key"]=this.databaseInfo.apiKey)}createWebChannelTransport(){return H2()}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function k3(r){return new as(r)}as.sn=!1;class M1{constructor(e,t,n=1e3,s=1.5,i=6e4){this.Tn=e,this.timerId=t,this.Pn=n,this.Rn=s,this.In=i,this.An=0,this.Vn=null,this.dn=Date.now(),this.reset()}reset(){this.An=0}fn(){this.An=this.In}mn(e){this.cancel();const t=Math.floor(this.An+this.pn()),n=Math.max(0,Date.now()-this.dn),s=Math.max(0,t-n);s>0&&L("ExponentialBackoff",`Backing off for ${s} ms (base delay: ${this.An} ms, delay with jitter: ${t} ms, last attempt: ${n} ms ago)`),this.Vn=this.Tn.enqueueAfterDelay(this.timerId,s,()=>(this.dn=Date.now(),e())),this.An*=this.Rn,this.An<this.Pn&&(this.An=this.Pn),this.An>this.In&&(this.An=this.In)}gn(){this.Vn!==null&&(this.Vn.skipDelay(),this.Vn=null)}cancel(){this.Vn!==null&&(this.Vn.cancel(),this.Vn=null)}pn(){return(Math.random()-.5)*this.An}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const rf="PersistentStream";class op{constructor(e,t,n,s,i,o,u,c){this.Tn=e,this.yn=n,this.wn=s,this.connection=i,this.authCredentialsProvider=o,this.appCheckCredentialsProvider=u,this.listener=c,this.state=0,this.bn=0,this.vn=null,this.Sn=null,this.stream=null,this.Dn=0,this.xn=new M1(e,t)}Cn(){return this.state===1||this.state===5||this.Fn()}Fn(){return this.state===2||this.state===3}start(){this.Dn=0,this.state!==4?this.auth():this.On()}async stop(){this.Cn()&&await this.close(0)}Mn(){this.state=0,this.xn.reset()}Nn(){this.Fn()&&this.vn===null&&(this.vn=this.Tn.enqueueAfterDelay(this.yn,6e4,()=>this.Ln()))}Bn(e){this.Un(),this.stream.send(e)}async Ln(){if(this.Fn())return this.close(0)}Un(){this.vn&&(this.vn.cancel(),this.vn=null)}kn(){this.Sn&&(this.Sn.cancel(),this.Sn=null)}async close(e,t){this.Un(),this.kn(),this.xn.cancel(),this.bn++,e!==4?this.xn.reset():t&&t.code===D.RESOURCE_EXHAUSTED?(ke(t.toString()),ke("Using maximum backoff delay to prevent overloading the backend."),this.xn.fn()):t&&t.code===D.UNAUTHENTICATED&&this.state!==3&&(this.authCredentialsProvider.invalidateToken(),this.appCheckCredentialsProvider.invalidateToken()),this.stream!==null&&(this.qn(),this.stream.close(),this.stream=null),this.state=e,await this.listener.Ht(t)}qn(){}auth(){this.state=1;const e=this.$n(this.bn),t=this.bn;Promise.all([this.authCredentialsProvider.getToken(),this.appCheckCredentialsProvider.getToken()]).then(([n,s])=>{this.bn===t&&this.Kn(n,s)},n=>{e(()=>{const s=new F(D.UNKNOWN,"Fetching auth token failed: "+n.message);return this.Wn(s)})})}Kn(e,t){const n=this.$n(this.bn);this.stream=this.Qn(e,t),this.stream.Qt(()=>{n(()=>this.listener.Qt())}),this.stream.zt(()=>{n(()=>(this.state=2,this.Sn=this.Tn.enqueueAfterDelay(this.wn,1e4,()=>(this.Fn()&&(this.state=3),Promise.resolve())),this.listener.zt()))}),this.stream.Ht(s=>{n(()=>this.Wn(s))}),this.stream.onMessage(s=>{n(()=>++this.Dn==1?this.Gn(s):this.onNext(s))})}On(){this.state=5,this.xn.mn(async()=>{this.state=0,this.start()})}Wn(e){return L(rf,`close with error: ${e}`),this.stream=null,this.close(4,e)}$n(e){return t=>{this.Tn.enqueueAndForget(()=>this.bn===e?t():(L(rf,"stream callback skipped by getCloseGuardedDispatcher."),Promise.resolve()))}}}class L3 extends op{constructor(e,t,n,s,i,o){super(e,"listen_stream_connection_backoff","listen_stream_idle","health_check_timeout",t,n,s,o),this.serializer=i}Qn(e,t){return this.connection.cn("Listen",e,t)}Gn(e){return this.onNext(e)}onNext(e){this.xn.reset();const t=A3(this.serializer,e),n=function(i){if(!("targetChange"in i))return K.min();const o=i.targetChange;return o.targetIds&&o.targetIds.length?K.min():o.readTime?Ke(o.readTime):K.min()}(e);return this.listener.zn(t,n)}jn(e){const t={};t.database=Hc(this.serializer),t.addTarget=function(i,o){let u;const c=o.target;if(u=nn(c)?{pipelineQuery:Z6(i,c)}:N1(c)?{documents:Y6(i,c)}:{query:X6(i,c).yt},u.targetId=o.targetId,o.resumeToken.approximateByteSize()>0){u.resumeToken=z6(i,o.resumeToken);const l=jc(i,o.expectedCount);l!==null&&(u.expectedCount=l)}else if(o.snapshotVersion.compareTo(K.min())>0){u.readTime=Rs(i,o.snapshotVersion.toTimestamp());const l=jc(i,o.expectedCount);l!==null&&(u.expectedCount=l)}return u}(this.serializer,e);const n=R3(this.serializer,e);n&&(t.labels=n),this.Bn(t)}Hn(e){const t={};t.database=Hc(this.serializer),t.removeTarget=e,this.Bn(t)}}class M3 extends op{constructor(e,t,n,s,i,o){super(e,"write_stream_connection_backoff","write_stream_idle","health_check_timeout",t,n,s,o),this.serializer=i}get Jn(){return this.Dn>0}start(){this.lastStreamToken=void 0,super.start()}qn(){this.Jn&&this.Yn([])}Qn(e,t){return this.connection.cn("Write",e,t)}Gn(e){return U(!!e.streamToken,31322),this.lastStreamToken=e.streamToken,U(!e.writeResults||e.writeResults.length===0,55816),this.listener.Zn()}onNext(e){U(!!e.streamToken,12678),this.lastStreamToken=e.streamToken,this.xn.reset();const t=v3(e.writeResults,e.commitTime),n=Ke(e.commitTime);return this.listener.Xn(n,t)}er(){const e={};e.database=Hc(this.serializer),this.Bn(e)}Yn(e){const t={streamToken:this.lastStreamToken,writes:e.map(n=>eo(this.serializer,n))};this.Bn(t)}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class F3{}class U3 extends F3{constructor(e,t,n,s){super(),this.authCredentials=e,this.appCheckCredentials=t,this.connection=n,this.serializer=s,this.tr=!1}nr(){if(this.tr)throw new F(D.FAILED_PRECONDITION,"The client has already been terminated.")}Bt(e,t,n,s){return this.nr(),Promise.all([this.authCredentials.getToken(),this.appCheckCredentials.getToken()]).then(([i,o])=>this.connection.Bt(e,zc(t,n),s,i,o)).catch(i=>{throw i.name==="FirebaseError"?(i.code===D.UNAUTHENTICATED&&(this.authCredentials.invalidateToken(),this.appCheckCredentials.invalidateToken()),i):new F(D.UNKNOWN,i.toString())})}$t(e,t,n,s,i){return this.nr(),Promise.all([this.authCredentials.getToken(),this.appCheckCredentials.getToken()]).then(([o,u])=>this.connection.$t(e,zc(t,n),s,o,u,i)).catch(o=>{throw o.name==="FirebaseError"?(o.code===D.UNAUTHENTICATED&&(this.authCredentials.invalidateToken(),this.appCheckCredentials.invalidateToken()),o):new F(D.UNKNOWN,o.toString())})}terminate(){this.tr=!0,this.connection.terminate()}}function B3(r,e,t,n){return new U3(r,e,t,n)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const q3="ComponentProvider",sf=new Map;function $3(r,e,t,n,s){return new U4(r,e,t,s.host,s.ssl,s.experimentalForceLongPolling,s.experimentalAutoDetectLongPolling,ip(s.experimentalLongPollingOptions),s.useFetchStreams,s.isUsingEmulator,n)}/**
 * @license
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const of={didRun:!1,sequenceNumbersCollected:0,targetsRemoved:0,documentsRemoved:0},ap=41943040;class tt{static withCacheSize(e){return new tt(e,tt.DEFAULT_COLLECTION_PERCENTILE,tt.DEFAULT_MAX_SEQUENCE_NUMBERS_TO_COLLECT)}constructor(e,t,n){this.cacheSizeCollectionThreshold=e,this.percentileToCollect=t,this.maximumSequenceNumbersToCollect=n}}tt.DEFAULT_COLLECTION_PERCENTILE=10,tt.DEFAULT_MAX_SEQUENCE_NUMBERS_TO_COLLECT=1e3,tt.DEFAULT=new tt(ap,tt.DEFAULT_COLLECTION_PERCENTILE,tt.DEFAULT_MAX_SEQUENCE_NUMBERS_TO_COLLECT),tt.DISABLED=new tt(-1,0,0);/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const af="LruGarbageCollector",up=1048576;function uf([r,e],[t,n]){const s=Z(r,t);return s===0?Z(e,n):s}class G3{constructor(e){this.rr=e,this.buffer=new ge(uf),this.ir=0}sr(){return++this.ir}_r(e){const t=[e,this.sr()];if(this.buffer.size<this.rr)this.buffer=this.buffer.add(t);else{const n=this.buffer.last();uf(t,n)<0&&(this.buffer=this.buffer.delete(n).add(t))}}get maxValue(){return this.buffer.last()[0]}}class cp{constructor(e,t,n){this.garbageCollector=e,this.asyncQueue=t,this.localStore=n,this.ar=null}start(){this.garbageCollector.params.cacheSizeCollectionThreshold!==-1&&this.ur(6e4)}stop(){this.ar&&(this.ar.cancel(),this.ar=null)}get started(){return this.ar!==null}ur(e){L(af,`Garbage collection scheduled in ${e}ms`),this.ar=this.asyncQueue.enqueueAfterDelay("lru_garbage_collection",e,async()=>{this.ar=null;try{await this.localStore.collectGarbage(this.garbageCollector)}catch(t){nr(t)?L(af,"Ignoring IndexedDB error during garbage collection: ",t):await tr(t)}await this.ur(3e5)})}}class j3{constructor(e,t){this.cr=e,this.params=t}calculateTargetCount(e,t){return this.cr.lr(e).next(n=>Math.floor(t/100*n))}nthSequenceNumber(e,t){if(t===0)return P.resolve(ht.ce);const n=new G3(t);return this.cr.forEachTarget(e,s=>n._r(s.sequenceNumber)).next(()=>this.cr.Er(e,s=>n._r(s))).next(()=>n.maxValue)}removeTargets(e,t,n){return this.cr.removeTargets(e,t,n)}removeOrphanedDocuments(e,t){return this.cr.removeOrphanedDocuments(e,t)}collect(e,t){return this.params.cacheSizeCollectionThreshold===-1?(L("LruGarbageCollector","Garbage collection skipped; disabled"),P.resolve(of)):this.getCacheSize(e).next(n=>n<this.params.cacheSizeCollectionThreshold?(L("LruGarbageCollector",`Garbage collection skipped; Cache size ${n} is lower than threshold ${this.params.cacheSizeCollectionThreshold}`),of):this.hr(e,t))}getCacheSize(e){return this.cr.getCacheSize(e)}hr(e,t){let n,s,i,o,u,c,l;const d=Date.now();return this.calculateTargetCount(e,this.params.percentileToCollect).next(g=>(g>this.params.maximumSequenceNumbersToCollect?(L("LruGarbageCollector",`Capping sequence numbers to collect down to the maximum of ${this.params.maximumSequenceNumbersToCollect} from ${g}`),s=this.params.maximumSequenceNumbersToCollect):s=g,o=Date.now(),this.nthSequenceNumber(e,s))).next(g=>(n=g,u=Date.now(),this.removeTargets(e,n,t))).next(g=>(i=g,c=Date.now(),this.removeOrphanedDocuments(e,n))).next(g=>(l=Date.now(),ts()<=ue.DEBUG&&L("LruGarbageCollector",`LRU Garbage Collection
	Counted targets in ${o-d}ms
	Determined least recently used ${s} in `+(u-o)+`ms
	Removed ${i} targets in `+(c-u)+`ms
	Removed ${g} documents in `+(l-c)+`ms
Total Duration: ${l-d}ms`),P.resolve({didRun:!0,sequenceNumbersCollected:s,targetsRemoved:i,documentsRemoved:g})))}}function lp(r,e){return new j3(r,e)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const z3="firestore.googleapis.com",cf=!0;class lf{constructor(e){if(e.host===void 0){if(e.ssl!==void 0)throw new F(D.INVALID_ARGUMENT,"Can't provide ssl option if host option is not set");this.host=z3,this.ssl=cf}else this.host=e.host,this.ssl=e.ssl??cf;if(this.isUsingEmulator=e.emulatorOptions!==void 0,this.credentials=e.credentials,this.ignoreUndefinedProperties=!!e.ignoreUndefinedProperties,this.localCache=e.localCache,e.cacheSizeBytes===void 0)this.cacheSizeBytes=ap;else{if(e.cacheSizeBytes!==-1&&e.cacheSizeBytes<up)throw new F(D.INVALID_ARGUMENT,"cacheSizeBytes must be at least 1048576");this.cacheSizeBytes=e.cacheSizeBytes}i4("experimentalForceLongPolling",e.experimentalForceLongPolling,"experimentalAutoDetectLongPolling",e.experimentalAutoDetectLongPolling),this.experimentalForceLongPolling=!!e.experimentalForceLongPolling,this.experimentalForceLongPolling?this.experimentalAutoDetectLongPolling=!1:e.experimentalAutoDetectLongPolling===void 0?this.experimentalAutoDetectLongPolling=!0:this.experimentalAutoDetectLongPolling=!!e.experimentalAutoDetectLongPolling,this.experimentalLongPollingOptions=ip(e.experimentalLongPollingOptions??{}),function(n){if(n.timeoutSeconds!==void 0){if(isNaN(n.timeoutSeconds))throw new F(D.INVALID_ARGUMENT,`invalid long polling timeout: ${n.timeoutSeconds} (must not be NaN)`);if(n.timeoutSeconds<5)throw new F(D.INVALID_ARGUMENT,`invalid long polling timeout: ${n.timeoutSeconds} (minimum allowed value is 5)`);if(n.timeoutSeconds>30)throw new F(D.INVALID_ARGUMENT,`invalid long polling timeout: ${n.timeoutSeconds} (maximum allowed value is 30)`)}}(this.experimentalLongPollingOptions),this.useFetchStreams=!!e.useFetchStreams}isEqual(e){return this.host===e.host&&this.ssl===e.ssl&&this.credentials===e.credentials&&this.cacheSizeBytes===e.cacheSizeBytes&&this.experimentalForceLongPolling===e.experimentalForceLongPolling&&this.experimentalAutoDetectLongPolling===e.experimentalAutoDetectLongPolling&&function(n,s){return n.timeoutSeconds===s.timeoutSeconds}(this.experimentalLongPollingOptions,e.experimentalLongPollingOptions)&&this.ignoreUndefinedProperties===e.ignoreUndefinedProperties&&this.useFetchStreams===e.useFetchStreams}}class F1{constructor(e,t,n,s){this._authCredentials=e,this._appCheckCredentials=t,this._databaseId=n,this._app=s,this.type="firestore-lite",this._persistenceKey="(lite)",this._settings=new lf({}),this._settingsFrozen=!1,this._emulatorOptions={},this._terminateTask="notTerminated"}get app(){if(!this._app)throw new F(D.FAILED_PRECONDITION,"Firestore was not initialized using the Firebase SDK. 'app' is not available");return this._app}get _initialized(){return this._settingsFrozen}get _terminated(){return this._terminateTask!=="notTerminated"}_setSettings(e){if(this._settingsFrozen)throw new F(D.FAILED_PRECONDITION,"Firestore has already been started and its settings can no longer be changed. You can only modify settings before calling any other methods on a Firestore object.");this._settings=new lf(e),this._emulatorOptions=e.emulatorOptions||{},e.credentials!==void 0&&(this._authCredentials=function(n){if(!n)return new Ym;switch(n.type){case"firstParty":return new Zm(n.sessionIndex||"0",n.iamToken||null,n.authTokenFactory||null);case"provider":return n.client;default:throw new F(D.INVALID_ARGUMENT,"makeAuthCredentialsProvider failed due to invalid credential type")}}(e.credentials))}_getSettings(){return this._settings}_getEmulatorOptions(){return this._emulatorOptions}_freezeSettings(){return this._settingsFrozen=!0,this._settings}_delete(){return this._terminateTask==="notTerminated"&&(this._terminateTask=this._terminate()),this._terminateTask}async _restart(){this._terminateTask==="notTerminated"?await this._terminate():this._terminateTask="notTerminated"}toJSON(){return{app:this._app,databaseId:this._databaseId,settings:this._settings}}_terminate(){return function(t){const n=sf.get(t);n&&(L(q3,"Removing Datastore"),sf.delete(t),n.terminate())}(this),Promise.resolve()}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class sr{constructor(e,t,n){this.converter=t,this._query=n,this.type="query",this.firestore=e}withConverter(e){return new sr(this.firestore,e,this._query)}}class Pe{constructor(e,t,n){this.converter=t,this._key=n,this.type="document",this.firestore=e}get _path(){return this._key.path}get id(){return this._key.path.lastSegment()}get path(){return this._key.path.canonicalString()}get parent(){return new Gn(this.firestore,this.converter,this._key.path.popLast())}withConverter(e){return new Pe(this.firestore,e,this._key)}toJSON(){return{type:Pe._jsonSchemaVersion,referencePath:this._key.toString()}}static fromJSON(e,t,n){if(po(t,Pe._jsonSchema))return new Pe(e,n||null,new $(ae.fromString(t.referencePath)))}}Pe._jsonSchemaVersion="firestore/documentReference/1.0",Pe._jsonSchema={type:Me("string",Pe._jsonSchemaVersion),referencePath:Me("string")};class Gn extends sr{constructor(e,t,n){super(e,t,yo(n)),this._path=n,this.type="collection"}get id(){return this._query.path.lastSegment()}get path(){return this._query.path.canonicalString()}get parent(){const e=this._path.popLast();return e.isEmpty()?null:new Pe(this.firestore,null,new $(e))}withConverter(e){return new Gn(this.firestore,e,this._path)}}function tw(r,e,...t){if(r=be(r),Y2("collection","path",e),r instanceof F1){const n=ae.fromString(e,...t);return Cd(n),new Gn(r,null,n)}{if(!(r instanceof Pe||r instanceof Gn))throw new F(D.INVALID_ARGUMENT,"Expected first argument to collection() to be a CollectionReference, a DocumentReference or FirebaseFirestore");const n=r._path.child(ae.fromString(e,...t));return Cd(n),new Gn(r.firestore,null,n)}}function H3(r,e,...t){if(r=be(r),arguments.length===1&&(e=g1.newId()),Y2("doc","path",e),r instanceof F1){const n=ae.fromString(e,...t);return bd(n),new Pe(r,null,new $(n))}{if(!(r instanceof Pe||r instanceof Gn))throw new F(D.INVALID_ARGUMENT,"Expected first argument to doc() to be a CollectionReference, a DocumentReference or FirebaseFirestore");const n=r._path.child(ae.fromString(e,...t));return bd(n),new Pe(r.firestore,r instanceof Gn?r.converter:null,new $(n))}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *//**
 * @license
 * Copyright 2024 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class ft{constructor(e){this._values=(e||[]).map(t=>t)}toArray(){return this._values.map(e=>e)}isEqual(e){return function(n,s){if(n.length!==s.length)return!1;for(let i=0;i<n.length;++i)if(n[i]!==s[i])return!1;return!0}(this._values,e._values)}toJSON(){return{type:ft._jsonSchemaVersion,vectorValues:this._values}}static fromJSON(e){if(po(e,ft._jsonSchema)){if(Array.isArray(e.vectorValues)&&e.vectorValues.every(t=>typeof t=="number"))return new ft(e.vectorValues);throw new F(D.INVALID_ARGUMENT,"Expected 'vectorValues' field to be a number array")}}}ft._jsonSchemaVersion="firestore/vectorValue/1.0",ft._jsonSchema={type:Me("string",ft._jsonSchemaVersion),vectorValues:Me("object")};/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const K3=/^__.*__$/;class W3{constructor(e,t,n){this.data=e,this.fieldMask=t,this.fieldTransforms=n}toMutation(e,t){return this.fieldMask!==null?new yn(e,this.data,this.fieldMask,t,this.fieldTransforms):new Ms(e,this.data,t,this.fieldTransforms)}}class hp{constructor(e,t,n){this.data=e,this.fieldMask=t,this.fieldTransforms=n}toMutation(e,t){return new yn(e,this.data,this.fieldMask,t,this.fieldTransforms)}}function dp(r){switch(r){case 0:case 2:case 1:return!0;case 3:case 4:return!1;default:throw j(40011,{dataSource:r})}}class U1{constructor(e,t,n,s,i,o){this.settings=e,this.databaseId=t,this.serializer=n,this.ignoreUndefinedProperties=s,i===void 0&&this.validatePath(),this.fieldTransforms=i||[],this.fieldMask=o||[]}get path(){return this.settings.path}get dataSource(){return this.settings.dataSource}contextWith(e){return new U1({...this.settings,...e},this.databaseId,this.serializer,this.ignoreUndefinedProperties,this.fieldTransforms,this.fieldMask)}childContextForField(e){var s;const t=(s=this.path)==null?void 0:s.child(e),n=this.contextWith({path:t,arrayElement:!1});return n.validatePathSegment(e),n}childContextForFieldPath(e){var s;const t=(s=this.path)==null?void 0:s.child(e),n=this.contextWith({path:t,arrayElement:!1});return n.validatePath(),n}childContextForArray(e){return this.contextWith({path:void 0,arrayElement:!0})}createError(e){return Da(e,this.settings.methodName,this.settings.hasConverter||!1,this.path,this.settings.targetDoc)}contains(e){return this.fieldMask.find(t=>e.isPrefixOf(t))!==void 0||this.fieldTransforms.find(t=>e.isPrefixOf(t.field))!==void 0}validatePath(){if(this.path)for(let e=0;e<this.path.length;e++)this.validatePathSegment(this.path.get(e))}validatePathSegment(e){if(e.length===0)throw this.createError("Document fields must not be empty");if(dp(this.dataSource)&&K3.test(e))throw this.createError('Document fields cannot begin and end with "__"')}}class Q3{constructor(e,t,n){this.databaseId=e,this.ignoreUndefinedProperties=t,this.serializer=n||du(e)}createContext(e,t,n,s=!1){return new U1({dataSource:e,methodName:t,targetDoc:n,path:ve.emptyPath(),arrayElement:!1,hasConverter:s},this.databaseId,this.serializer,this.ignoreUndefinedProperties)}}function $s(r){const e=r._freezeSettings(),t=du(r._databaseId);return new Q3(r._databaseId,!!e.ignoreUndefinedProperties,t)}function fu(r,e,t,n,s,i={}){const o=r.createContext(i.merge||i.mergeFields?2:0,e,t,s);$1("Data must be an object, but it was:",o,n);const u=fp(n,o);let c,l;if(i.merge)c=new dt(o.fieldMask),l=o.fieldTransforms;else if(i.mergeFields){const d=[];for(const g of i.mergeFields){const y=fn(e,g,t);if(!o.contains(y))throw new F(D.INVALID_ARGUMENT,`Field '${y}' is specified in your field mask but missing from your input data.`);mp(d,y)||d.push(y)}c=new dt(d),l=o.fieldTransforms.filter(g=>c.covers(g.field))}else c=null,l=o.fieldTransforms;return new W3(new ze(u),c,l)}class Io extends L1{_toFieldTransform(e){if(e.dataSource!==2)throw e.dataSource===1?e.createError(`${this._methodName}() can only appear at the top level of your update data`):e.createError(`${this._methodName}() cannot be used with set() unless you pass {merge:true}`);return e.fieldMask.push(e.path),null}isEqual(e){return e instanceof Io}}function B1(r,e,t,n){const s=r.createContext(1,e,t);$1("Data must be an object, but it was:",s,n);const i=[],o=ze.empty();rr(n,(c,l)=>{const d=gp(e,c,t);l=be(l);const g=s.childContextForFieldPath(d);if(l instanceof Io)i.push(d);else{const y=Qn(l,g);y!=null&&(i.push(d),o.set(d,y))}});const u=new dt(i);return new hp(o,u,s.fieldTransforms)}function q1(r,e,t,n,s,i){const o=r.createContext(1,e,t),u=[fn(e,n,t)],c=[s];if(i.length%2!=0)throw new F(D.INVALID_ARGUMENT,`Function ${e}() needs to be called with an even number of arguments that alternate between field names and values.`);for(let y=0;y<i.length;y+=2)u.push(fn(e,i[y])),c.push(i[y+1]);const l=[],d=ze.empty();for(let y=u.length-1;y>=0;--y)if(!mp(l,u[y])){const R=u[y];let C=c[y];C=be(C);const M=o.childContextForFieldPath(R);if(C instanceof Io)l.push(R);else{const q=Qn(C,M);q!=null&&(l.push(R),d.set(R,q))}}const g=new dt(l);return new hp(d,g,o.fieldTransforms)}function Y3(r,e,t,n=!1){return Qn(t,r.createContext(n?4:3,e))}function Qn(r,e,t){if(pp(r=be(r)))return $1("Unsupported field value:",e,r),fp(r,e);if(r instanceof L1)return function(s,i){if(!dp(i.dataSource))throw i.createError(`${s._methodName}() can only be used with update() and set()`);if(!i.path)throw i.createError(`${s._methodName}() is not currently supported inside arrays`);const o=s._toFieldTransform(i);o&&i.fieldTransforms.push(o)}(r,e),null;if(r===void 0&&e.ignoreUndefinedProperties)return null;if(e.path&&e.fieldMask.push(e.path),r instanceof Array){if(e.settings.arrayElement&&e.dataSource!==4)throw e.createError("Nested arrays are not supported");return function(s,i){const o=[];let u=0;for(const c of s){let l=Qn(c,i.childContextForArray(u));l==null&&(l={nullValue:"NULL_VALUE"}),o.push(l),u++}return{arrayValue:{values:o}}}(r,e)}return function(s,i,o){if((s=be(s))===null)return{nullValue:"NULL_VALUE"};if(typeof s=="number")return R1(i.serializer,s,o);if(typeof s=="boolean")return{booleanValue:s};if(typeof s=="string")return{stringValue:s};if(s instanceof Date){const u=me.fromDate(s);return{timestampValue:Rs(i.serializer,u)}}if(s instanceof me){const u=new me(s.seconds,1e3*Math.floor(s.nanoseconds/1e3));return{timestampValue:Rs(i.serializer,u)}}if(s instanceof Wt)return{geoPointValue:{latitude:s.latitude,longitude:s.longitude}};if(s instanceof Et)return{bytesValue:z6(i.serializer,s._byteString)};if(s instanceof Pe){const u=i.databaseId,c=s.firestore._databaseId;if(!c.isEqual(u))throw i.createError(`Document reference is for database ${c.projectId}/${c.database} but should be for database ${u.projectId}/${u.database}`);return{referenceValue:k1(s.firestore._databaseId||i.databaseId,s._key.path)}}if(s instanceof ft)return function(c,l){const d=c instanceof ft?c.toArray():c;return{mapValue:{fields:{[T1]:{stringValue:A1},[Dr]:{arrayValue:{values:d.map(y=>{if(typeof y!="number")throw l.createError("VectorValues must only contain numeric values.");return uu(l.serializer,y)})}}}}}}(s,i);if(rp(s))return s._toProto(i.serializer);throw i.createError(`Unsupported field value: ${nu(s)}`)}(r,e,t)}function fp(r,e){const t={};return f6(r)?e.path&&e.path.length>0&&e.fieldMask.push(e.path):rr(r,(n,s)=>{const i=Qn(s,e.childContextForField(n));i!=null&&(t[n]=i)}),{mapValue:{fields:t}}}function pp(r){return!(typeof r!="object"||r===null||r instanceof Array||r instanceof Date||r instanceof me||r instanceof Wt||r instanceof Et||r instanceof Pe||r instanceof L1||r instanceof ft||rp(r))}function $1(r,e,t){if(!pp(t)||!fo(t)){const n=nu(t);throw n==="an object"?e.createError(r+" a custom object"):e.createError(r+" "+n)}}function fn(r,e,t){if((e=be(e))instanceof qs)return e._internalPath;if(typeof e=="string")return gp(r,e);throw Da("Field path arguments must be of type string or ",r,!1,void 0,t)}const X3=new RegExp("[~\\*/\\[\\]]");function gp(r,e,t){if(e.search(X3)>=0)throw Da(`Invalid field path (${e}). Paths must not contain '~', '*', '/', '[', or ']'`,r,!1,void 0,t);try{return new qs(...e.split("."))._internalPath}catch{throw Da(`Invalid field path (${e}). Paths must not be empty, begin with '.', end with '.', or contain '..'`,r,!1,void 0,t)}}function Da(r,e,t,n,s){const i=n&&!n.isEmpty(),o=s!==void 0;let u=`Function ${e}() called with invalid data`;t&&(u+=" (via `toFirestore()`)"),u+=". ";let c="";return(i||o)&&(c+=" (found",i&&(c+=` in field ${n}`),o&&(c+=` in document ${s}`),c+=")"),new F(D.INVALID_ARGUMENT,u+r+c)}function mp(r,e){return r.some(t=>t.isEqual(e))}function _p(r){return typeof r._readUserData=="function"}/**
 * @license
 * Copyright 2025 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class it{constructor(e){this.optionDefinitions=e}_getKnownOptions(e,t){const n=ze.empty();for(const s in this.optionDefinitions)if(this.optionDefinitions.hasOwnProperty(s)){const i=this.optionDefinitions[s];if(s in e){const o=e[s];let u;i.nestedOptions&&fo(o)?u={mapValue:{fields:new it(i.nestedOptions).getOptionsProto(t,o)}}:o&&(u=Qn(o,t)??void 0),u&&n.set(ve.fromServerFormat(i.serverName),u)}}return n}getOptionsProto(e,t,n){const s=this._getKnownOptions(t,e);if(n){const i=new Map(M4(n,(o,u)=>[ve.fromServerFormat(u),o!==void 0?Qn(o,e):null]));s.setAll(i)}return s.value.mapValue.fields??{}}}/**
 * @license
 * Copyright 2024 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function J3(r){return typeof r=="object"&&r!==null&&!!("nullValue"in r&&(r.nullValue===null||r.nullValue==="NULL_VALUE")||"booleanValue"in r&&(r.booleanValue===null||typeof r.booleanValue=="boolean")||"integerValue"in r&&(r.integerValue===null||typeof r.integerValue=="number"||typeof r.integerValue=="string")||"doubleValue"in r&&(r.doubleValue===null||typeof r.doubleValue=="number")||"timestampValue"in r&&(r.timestampValue===null||function(t){return typeof t=="object"&&t!==null&&"seconds"in t&&(t.seconds===null||typeof t.seconds=="number"||typeof t.seconds=="string")&&"nanos"in t&&(t.nanos===null||typeof t.nanos=="number")}(r.timestampValue))||"stringValue"in r&&(r.stringValue===null||typeof r.stringValue=="string")||"bytesValue"in r&&(r.bytesValue===null||r.bytesValue instanceof Uint8Array)||"referenceValue"in r&&(r.referenceValue===null||typeof r.referenceValue=="string")||"geoPointValue"in r&&(r.geoPointValue===null||function(t){return typeof t=="object"&&t!==null&&"latitude"in t&&(t.latitude===null||typeof t.latitude=="number")&&"longitude"in t&&(t.longitude===null||typeof t.longitude=="number")}(r.geoPointValue))||"arrayValue"in r&&(r.arrayValue===null||function(t){return typeof t=="object"&&t!==null&&!(!("values"in t)||t.values!==null&&!Array.isArray(t.values))}(r.arrayValue))||"mapValue"in r&&(r.mapValue===null||function(t){return typeof t=="object"&&t!==null&&!(!("fields"in t)||t.fields!==null&&!fo(t.fields))}(r.mapValue))||"fieldReferenceValue"in r&&(r.fieldReferenceValue===null||typeof r.fieldReferenceValue=="string")||"functionValue"in r&&(r.functionValue===null||function(t){return typeof t=="object"&&t!==null&&!(!("name"in t)||t.name!==null&&typeof t.name!="string"||!("args"in t)||t.args!==null&&!Array.isArray(t.args))}(r.functionValue))||"pipelineValue"in r&&(r.pipelineValue===null||function(t){return typeof t=="object"&&t!==null&&!(!("stages"in t)||t.stages!==null&&!Array.isArray(t.stages))}(r.pipelineValue)))}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function nw(){return new Io("deleteField")}function Z3(r){return new ft(r)}/**
 * @license
 * Copyright 2024 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function B(r){let e;return r instanceof qr?r:(e=fo(r)?s9(r):r instanceof Array?i9(r):yp(r,void 0),e)}function lc(r){if(r instanceof qr)return r;if(r instanceof ft)return no(r);if(Array.isArray(r))return no(Z3(r));throw new Error("Unsupported value: "+typeof r)}function G1(r){return l4(r)?pa(r):B(r)}class qr{constructor(){this._protoValueType="ProtoValue"}add(e){return new O("add",[this,B(e)],"add")}asBoolean(){if(this instanceof Yn)return this;if(this instanceof Gr)return new Ip(this);if(this instanceof $r)return new r9(this);if(this instanceof O)return new Ep(this);throw new F("invalid-argument",`Conversion of type ${typeof this} to BooleanExpression not supported.`)}subtract(e){return new O("subtract",[this,B(e)],"subtract")}multiply(e){return new O("multiply",[this,B(e)],"multiply")}divide(e){return new O("divide",[this,B(e)],"divide")}mod(e){return new O("mod",[this,B(e)],"mod")}equal(e){return new O("equal",[this,B(e)],"equal").asBoolean()}notEqual(e){return new O("not_equal",[this,B(e)],"notEqual").asBoolean()}lessThan(e){return new O("less_than",[this,B(e)],"lessThan").asBoolean()}lessThanOrEqual(e){return new O("less_than_or_equal",[this,B(e)],"lessThanOrEqual").asBoolean()}greaterThan(e){return new O("greater_than",[this,B(e)],"greaterThan").asBoolean()}greaterThanOrEqual(e){return new O("greater_than_or_equal",[this,B(e)],"greaterThanOrEqual").asBoolean()}arrayConcat(e,...t){const n=[e,...t].map(s=>B(s));return new O("array_concat",[this,...n],"arrayConcat")}arrayContains(e){return new O("array_contains",[this,B(e)],"arrayContains").asBoolean()}arrayContainsAll(e){const t=Array.isArray(e)?new Ai(e.map(B),"arrayContainsAll"):e;return new O("array_contains_all",[this,t],"arrayContainsAll").asBoolean()}arrayContainsAny(e){const t=Array.isArray(e)?new Ai(e.map(B),"arrayContainsAny"):e;return new O("array_contains_any",[this,t],"arrayContainsAny").asBoolean()}arrayReverse(){return new O("array_reverse",[this])}arrayLength(){return new O("array_length",[this],"arrayLength")}equalAny(e){const t=Array.isArray(e)?new Ai(e.map(B),"equalAny"):e;return new O("equal_any",[this,t],"equalAny").asBoolean()}notEqualAny(e){const t=Array.isArray(e)?new Ai(e.map(B),"notEqualAny"):e;return new O("not_equal_any",[this,t],"notEqualAny").asBoolean()}exists(){return new O("exists",[this],"exists").asBoolean()}charLength(){return new O("char_length",[this],"charLength")}like(e){return new O("like",[this,B(e)],"like").asBoolean()}regexContains(e){return new O("regex_contains",[this,B(e)],"regexContains").asBoolean()}regexFind(e){return new O("regex_find",[this,B(e)],"regexFind")}regexFindAll(e){return new O("regex_find_all",[this,B(e)],"regexFindAll")}regexMatch(e){return new O("regex_match",[this,B(e)],"regexMatch").asBoolean()}stringContains(e){return new O("string_contains",[this,B(e)],"stringContains").asBoolean()}startsWith(e){return new O("starts_with",[this,B(e)],"startsWith").asBoolean()}endsWith(e){return new O("ends_with",[this,B(e)],"endsWith").asBoolean()}toLower(){return new O("to_lower",[this],"toLower")}toUpper(){return new O("to_upper",[this],"toUpper")}trim(e){const t=[this];return e&&t.push(B(e)),new O("trim",t,"trim")}ltrim(e){const t=[this];return e&&t.push(B(e)),new O("ltrim",t,"ltrim")}rtrim(e){const t=[this];return e&&t.push(B(e)),new O("rtrim",t,"rtrim")}type(){return new O("type",[this])}isType(e){return new O("is_type",[this,no(e)],"isType").asBoolean()}stringConcat(e,...t){const n=[e,...t].map(B);return new O("string_concat",[this,...n],"stringConcat")}stringIndexOf(e){return new O("string_index_of",[this,B(e)],"stringIndexOf")}stringRepeat(e){return new O("string_repeat",[this,B(e)],"stringRepeat")}stringReplaceAll(e,t){return new O("string_replace_all",[this,B(e),B(t)],"stringReplaceAll")}stringReplaceOne(e,t){return new O("string_replace_one",[this,B(e),B(t)],"stringReplaceOne")}concat(e,...t){const n=[e,...t].map(B);return new O("concat",[this,...n],"concat")}reverse(){return new O("reverse",[this],"reverse")}arrayFilter(e,t){return new O("array_filter",[this,B(e),t],"arrayFilter")}arrayTransform(e,t){return new O("array_transform",[this,B(e),t],"arrayTransform")}arrayTransformWithIndex(e,t,n){return new O("array_transform",[this,B(e),B(t),n],"arrayTransformWithIndex")}arraySlice(e,t){const n=[this,B(e)];return t!==void 0&&n.push(B(t)),new O("array_slice",n,"arraySlice")}arrayFirst(){return new O("array_first",[this],"arrayFirst")}arrayFirstN(e){return new O("array_first_n",[this,B(e)],"arrayFirstN")}arrayLast(){return new O("array_last",[this],"arrayLast")}arrayLastN(e){return new O("array_last_n",[this,B(e)],"arrayLastN")}arrayMaximum(){return new O("maximum",[this],"arrayMaximum")}arrayMaximumN(e){return new O("maximum_n",[this,B(e)],"arrayMaximumN")}arrayMinimum(){return new O("minimum",[this],"arrayMinimum")}arrayMinimumN(e){return new O("minimum_n",[this,B(e)],"arrayMinimumN")}arrayIndexOf(e){return new O("array_index_of",[this,B(e),B("first")],"arrayIndexOf")}arrayLastIndexOf(e){return new O("array_index_of",[this,B(e),B("last")],"arrayLastIndexOf")}arrayIndexOfAll(e){return new O("array_index_of_all",[this,B(e)],"arrayIndexOfAll")}byteLength(){return new O("byte_length",[this],"byteLength")}ceil(){return new O("ceil",[this])}floor(){return new O("floor",[this])}abs(){return new O("abs",[this])}exp(){return new O("exp",[this])}mapGet(e){return new O("map_get",[this,no(e)],"mapGet")}mapSet(e,t,...n){const s=[this,B(e),B(t),...n.map(B)];return new O("map_set",s,"mapSet")}mapKeys(){return new O("map_keys",[this],"mapKeys")}mapValues(){return new O("map_values",[this],"mapValues")}mapEntries(){return new O("map_entries",[this],"mapEntries")}getField(e){return new O("get_field",[this,B(e)],"get_field")}count(){return _t._create("count",[this],"count")}sum(){return _t._create("sum",[this],"sum")}average(){return _t._create("average",[this],"average")}minimum(){return _t._create("minimum",[this],"minimum")}maximum(){return _t._create("maximum",[this],"maximum")}first(){return _t._create("first",[this],"first")}last(){return _t._create("last",[this],"last")}arrayAgg(){return _t._create("array_agg",[this],"arrayAgg")}arrayAggDistinct(){return _t._create("array_agg_distinct",[this],"arrayAggDistinct")}countDistinct(){return _t._create("count_distinct",[this],"countDistinct")}logicalMaximum(e,...t){const n=[e,...t];return new O("maximum",[this,...n.map(B)],"logicalMaximum")}logicalMinimum(e,...t){const n=[e,...t];return new O("minimum",[this,...n.map(B)],"minimum")}vectorLength(){return new O("vector_length",[this],"vectorLength")}cosineDistance(e){return new O("cosine_distance",[this,lc(e)],"cosineDistance")}dotProduct(e){return new O("dot_product",[this,lc(e)],"dotProduct")}euclideanDistance(e){return new O("euclidean_distance",[this,lc(e)],"euclideanDistance")}unixMicrosToTimestamp(){return new O("unix_micros_to_timestamp",[this],"unixMicrosToTimestamp")}timestampToUnixMicros(){return new O("timestamp_to_unix_micros",[this],"timestampToUnixMicros")}unixMillisToTimestamp(){return new O("unix_millis_to_timestamp",[this],"unixMillisToTimestamp")}timestampToUnixMillis(){return new O("timestamp_to_unix_millis",[this],"timestampToUnixMillis")}unixSecondsToTimestamp(){return new O("unix_seconds_to_timestamp",[this],"unixSecondsToTimestamp")}timestampToUnixSeconds(){return new O("timestamp_to_unix_seconds",[this],"timestampToUnixSeconds")}timestampAdd(e,t){return new O("timestamp_add",[this,B(e),B(t)],"timestampAdd")}timestampSubtract(e,t){return new O("timestamp_subtract",[this,B(e),B(t)],"timestampSubtract")}timestampDiff(e,t){return new O("timestamp_diff",[this,G1(e),B(t)],"timestampDiff")}timestampExtract(e,t){const n=[this,B(e)];return t&&n.push(B(t)),new O("timestamp_extract",n,"timestampExtract")}documentId(){return new O("document_id",[this],"documentId")}parent(){return new O("parent",[this],"parent")}substring(e,t){const n=B(e);return new O("substring",t===void 0?[this,n]:[this,n,B(t)],"substring")}arrayGet(e){return new O("array_get",[this,B(e)],"arrayGet")}isError(){return new O("is_error",[this],"isError").asBoolean()}ifError(e){const t=new O("if_error",[this,B(e)],"ifError");return e instanceof Yn?t.asBoolean():t}isAbsent(){return new O("is_absent",[this],"isAbsent").asBoolean()}mapRemove(e){return new O("map_remove",[this,B(e)],"mapRemove")}mapMerge(e,...t){const n=B(e),s=t.map(B);return new O("map_merge",[this,n,...s],"mapMerge")}pow(e){return new O("pow",[this,B(e)])}trunc(e){return e===void 0?new O("trunc",[this]):new O("trunc",[this,B(e)],"trunc")}round(e){return e===void 0?new O("round",[this]):new O("round",[this,B(e)],"round")}collectionId(){return new O("collection_id",[this])}length(){return new O("length",[this])}ln(){return new O("ln",[this])}sqrt(){return new O("sqrt",[this])}stringReverse(){return new O("string_reverse",[this])}ifAbsent(e){return new O("if_absent",[this,B(e)],"ifAbsent")}ifNull(e){return new O("if_null",[this,B(e)],"ifNull")}coalesce(e,...t){return new O("coalesce",[this,B(e),...t.map(B)],"coalesce")}join(e){return new O("join",[this,B(e)],"join")}log10(){return new O("log10",[this])}arraySum(){return new O("sum",[this])}split(e){return new O("split",[this,B(e)])}timestampTruncate(e,t){const n=[this,B(e)];return t&&n.push(B(t)),new O("timestamp_trunc",n)}ascending(){return o9(this)}descending(){return a9(this)}as(e){return new t9(this,e,"as")}}class _t{constructor(e,t){this.name=e,this.params=t,this.exprType="AggregateFunction",this._protoValueType="ProtoValue"}static _create(e,t,n){const s=new _t(e,t);return s._methodName=n,s}as(e){return new e9(this,e,"as")}_toProto(e){return{functionValue:{name:this.name,args:this.params.map(t=>t._toProto(e))}}}_readUserData(e){e=this._methodName?e.contextWith({methodName:this._methodName}):e,this.params.forEach(t=>t._readUserData(e))}}class e9{constructor(e,t,n){this.aggregate=e,this.alias=t,this._methodName=n}_readUserData(e){this.aggregate._readUserData(e)}}class t9{constructor(e,t,n){this.expr=e,this.alias=t,this._methodName=n,this.exprType="AliasedExpression",this.selectable=!0}_readUserData(e){this.expr._readUserData(e)}}class Ai extends qr{constructor(e,t){super(),this.Rr=e,this._methodName=t,this.expressionType="ListOfExpressions"}_toProto(e){return{arrayValue:{values:this.Rr.map(t=>t._toProto(e))}}}_readUserData(e){this.Rr.forEach(t=>t._readUserData(e))}}class $r extends qr{constructor(e,t){super(),this.fieldPath=e,this._methodName=t,this.expressionType="Field",this.selectable=!0}get _fieldPath(){return this.fieldPath}get fieldName(){return this.fieldPath.canonicalString()}get alias(){return this.fieldName}get expr(){return this}geoDistance(e){return new O("geo_distance",[this,B(e)],"geoDistance")}_toProto(e){return{fieldReferenceValue:this.fieldPath.canonicalString()}}_readUserData(e){}}function pa(r){return n9(r,"field")}function n9(r,e){return new $r(typeof r=="string"?$t===r?N3()._internalPath:fn("field",r):r._internalPath,e)}class Gr extends qr{constructor(e,t){super(),this.value=e,this._methodName=t,this.expressionType="Constant"}static _fromProto(e){const t=new Gr(e,void 0);return t._protoValue=e,t}_toProto(e){return U(this._protoValue!==void 0,237),this._protoValue}_getValue(){return this._protoValue}_readUserData(e){e=this._methodName?e.contextWith({methodName:this._methodName}):e,J3(this._protoValue)||(this._protoValue=Qn(this.value,e))}}function no(r,e){return yp(r,"constant")}function yp(r,e){const t=new Gr(r,e);return typeof r=="boolean"?new Ip(t):t}class O extends qr{constructor(e,t,n,s){super(),this.name=e,this.params=t,this.expressionType="Function",this._optionsProto=void 0,n!==void 0&&(this._methodName=n),s!==void 0&&(this._options=s)}get _optionsUtil(){return new it({})}_toProto(e){const t={functionValue:{name:this.name,args:this.params.map(n=>n._toProto(e))}};return this._optionsProto&&(t.functionValue.options=this._optionsProto),t}_readUserData(e){e=this._methodName?e.contextWith({methodName:this._methodName}):e,this.params.forEach(t=>t._readUserData(e)),this._options&&(this._optionsProto=this._optionsUtil.getOptionsProto(e,this._options))}}class Yn extends qr{get _methodName(){return this._expr._methodName}countIf(){return _t._create("count_if",[this],"countIf")}not(){return new O("not",[this],"not").asBoolean()}conditional(e,t){return new O("conditional",[this,e,t],"conditional")}ifError(e){const t=B(e),n=new O("if_error",[this,t],"ifError");return t instanceof Yn?n.asBoolean():n}_toProto(e){return this._expr._toProto(e)}_readUserData(e){this._expr._readUserData(e)}}class Ep extends Yn{constructor(e){super(),this._expr=e,this.expressionType="Function"}}class Ip extends Yn{constructor(e){super(),this._expr=e,this.expressionType="Constant"}_getValue(){return this._expr._getValue()}}class r9 extends Yn{constructor(e){super(),this._expr=e,this.expressionType="Field"}}function s9(r,e){const t=[];for(const n in r)if(Object.prototype.hasOwnProperty.call(r,n)){const s=r[n];t.push(no(n)),t.push(B(s))}return new O("map",t,"map")}function i9(r){return function(t,n){return new O("array",t.map(s=>B(s)),n)}(r,"array")}function o9(r){return new j1(G1(r),"ascending","ascending")}function a9(r){return new j1(G1(r),"descending","descending")}class j1{constructor(e,t,n){this.expr=e,this.direction=t,this._methodName=n,this._protoValueType="ProtoValue"}_toProto(e){return{mapValue:{fields:{direction:sp(this.direction),expression:this.expr._toProto(e)}}}}_readUserData(e){this.expr._readUserData(e)}}/**
 * @license
 * Copyright 2024 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class At{constructor(e){this.optionsProto=void 0,{rawOptions:this.rawOptions,...this.knownOptions}=e}_readUserData(e){this.optionsProto=this._optionsUtil.getOptionsProto(e,this.knownOptions,this.rawOptions)}_toProto(e){return{name:this._name,options:this.optionsProto}}}class wp extends At{get _name(){return"add_fields"}get _optionsUtil(){return new it({})}constructor(e,t){super(t),this.fields=e}_toProto(e){return{...super._toProto(e),args:[to(e,this.fields)]}}_readUserData(e){super._readUserData(e),Jn(this.fields,e)}}class Tp extends At{get _name(){return"aggregate"}get _optionsUtil(){return new it({})}constructor(e,t,n){super(n),this.groups=e,this.accumulators=t}_toProto(e){return{...super._toProto(e),args:[to(e,this.accumulators),to(e,this.groups)]}}_readUserData(e){super._readUserData(e),Jn(this.groups,e),Jn(this.accumulators,e)}}class Ap extends At{get _name(){return"distinct"}get _optionsUtil(){return new it({})}constructor(e,t){super(t),this.groups=e}_toProto(e){return{...super._toProto(e),args:[to(e,this.groups)]}}_readUserData(e){super._readUserData(e),Jn(this.groups,e)}}class wo extends At{get _name(){return"collection"}get _optionsUtil(){return new it({forceIndex:{serverName:"force_index"}})}constructor(e,t){super(t),this.Vr=e.startsWith("/")?e:"/"+e}_toProto(e){return{...super._toProto(e),args:[{referenceValue:this.Vr}]}}_readUserData(e){super._readUserData(e)}}class To extends At{get _name(){return"collection_group"}get _optionsUtil(){return new it({forceIndex:{serverName:"force_index"}})}constructor(e,t){super(t),this.collectionId=e}_toProto(e){return{...super._toProto(e),args:[{referenceValue:""},{stringValue:this.collectionId}]}}_readUserData(e){super._readUserData(e)}}class pu extends At{get _name(){return"database"}get _optionsUtil(){return new it({})}_toProto(e){return{...super._toProto(e)}}_readUserData(e){super._readUserData(e)}}class gu extends At{get _name(){return"documents"}get _optionsUtil(){return new it({})}constructor(e,t){if(super(t),!e||e.length===0)throw new F(D.INVALID_ARGUMENT,"Empty document paths are not allowed in DocumentsSource");const n=e.map(i=>i.startsWith("/")?i:"/"+i),s=new Set(n);if(s.size!==n.length)throw new F(D.INVALID_ARGUMENT,"Duplicate document paths are not allowed in DocumentsSource");this.dr=n,this.mr=s}_toProto(e){return{...super._toProto(e),args:this.dr.map(t=>({referenceValue:t}))}}_readUserData(e){super._readUserData(e)}}class Ao extends At{get _name(){return"where"}get _optionsUtil(){return new it({})}constructor(e,t){super(t),this.condition=e}_toProto(e){return{...super._toProto(e),args:[this.condition._toProto(e)]}}_readUserData(e){super._readUserData(e),Jn(this.condition,e)}}class Xn extends At{get _name(){return"limit"}get _optionsUtil(){return new it({})}constructor(e,t){U(!isNaN(e)&&e!==1/0&&e!==-1/0,34860),super(t),this.limit=e}_toProto(e){return{...super._toProto(e),args:[R1(e,this.limit)]}}}class hf extends At{get _name(){return"offset"}get _optionsUtil(){return new it({})}constructor(e,t){super(t),this.offset=e}_toProto(e){return{...super._toProto(e),args:[R1(e,this.offset)]}}}class u9 extends At{get _name(){return"select"}get _optionsUtil(){return new it({})}constructor(e,t){super(t),this.selections=e}_toProto(e){return{...super._toProto(e),args:[to(e,this.selections)]}}_readUserData(e){super._readUserData(e),Jn(this.selections,e)}}class zt extends At{get _name(){return"sort"}get _optionsUtil(){return new it({})}constructor(e,t){super(t),this.orderings=e}_toProto(e){return{...super._toProto(e),args:this.orderings.map(t=>t._toProto(e))}}_readUserData(e){super._readUserData(e),Jn(this.orderings,e)}}class z1 extends At{get _name(){return"replace_with"}get _optionsUtil(){return new it({})}constructor(e,t){super(t),this.map=e}_toProto(e){return{...super._toProto(e),args:[this.map._toProto(e),sp(z1.pr)]}}_readUserData(e){super._readUserData(e),Jn(this.map,e)}}z1.pr="full_replace";function Jn(r,e){return _p(r)?r._readUserData(e):Array.isArray(r)?r.forEach(t=>t._readUserData(e)):r instanceof Map?r.forEach(t=>t._readUserData(e)):Object.values(r).forEach(t=>t._readUserData(e)),r}// Copyright 2024 Google LLC* @license
class nt{constructor(e,t,n){this.serializer=e,this.stages=t,this.listenOptions=n,this.isCorePipeline=!0}getPipelineCollection(){return vo(this)}getPipelineCollectionGroup(){return H1(this)}getPipelineCollectionId(){return vp(this)}getPipelineDocuments(){return Oa(this)}getPipelineFlavor(){return function(t){let n="exact";return t.stages.forEach((s,i)=>{s._name!==Ap.name&&s._name!==Tp.name||(n="keyless"),s._name===u9.name&&n==="exact"&&(n="augmented"),s._name===wp.name&&i<t.stages.length-1&&n==="exact"&&(n="augmented")}),n}(this)}getPipelineSourceType(){return un(this)}}function un(r){const e=r.stages[0];return e instanceof wo||e instanceof To||e instanceof pu||e instanceof gu?e._name:"unknown"}function vo(r){if(un(r)==="collection")return r.stages[0].Vr}function H1(r){if(un(r)==="collection_group")return r.stages[0].collectionId}function vp(r){switch(un(r)){case"collection":return ae.fromString(vo(r)).lastSegment();case"collection_group":return H1(r);default:return}}function Oa(r){if(un(r)==="documents")return r.stages[0].dr}class Li{constructor(e,t,n,s){this._db=e,this.userDataReader=t,this._userDataWriter=n,this.stages=s}wr(e,t){const n=this.userDataReader.createContext(3,e);return _p(t)?t._readUserData(n):Array.isArray(t)?t.forEach(s=>s._readUserData(n)):t.forEach(s=>s._readUserData(n)),t}where(e){const t=this.stages.map(n=>n);return this.wr("where",e),t.push(new Ao(e,{})),new Li(this._db,this.userDataReader,this._userDataWriter,t)}limit(e){const t=this.stages.map(n=>n);return t.push(new Xn(e,{})),new Li(this._db,this.userDataReader,this._userDataWriter,t)}sort(e,...t){const n=this.stages.map(s=>s);return"orderings"in e?n.push(new zt(this.wr("sort",e.orderings),{})):n.push(new zt(this.wr("sort",[e,...t]),{})),new Li(this._db,this.userDataReader,this._userDataWriter,n)}br(e){return{pipeline:{stages:this.stages.map(t=>t._toProto(e))}}}}// Copyright 2024 Google LLC* @license
class T{constructor(e,t){this.type=e,this.value=t}static vr(){return new T("ERROR",void 0)}static Sr(){return new T("UNSET",void 0)}static Dr(){return new T("NULL",Kt)}static newValue(e){return It(e)?new T("NULL",Kt):function(n){return!!n&&"booleanValue"in n}(e)?new T("BOOLEAN",e):jt(e)?new T("INT",e):wr(e)?new T("DOUBLE",e):function(n){return!!n&&"timestampValue"in n&&!!n.timestampValue}(e)?new T("TIMESTAMP",e):function(n){return!!n&&"stringValue"in n}(e)?new T("STRING",e):function(n){return!!n&&"bytesValue"in n}(e)?new T("BYTES",e):e.referenceValue?new T("REFERENCE",e):e.geoPointValue?new T("GEO_POINT",e):Wn(e)?new T("ARRAY",e):Or(e)?new T("VECTOR",e):Sr(e)?new T("MAP",e):new T("ERROR",void 0)}Cr(){return this.type==="ERROR"||this.type==="UNSET"}Fr(){return this.type==="NULL"}}function Mi(r){if(!r.Cr())return r.value}function Rp(r){return r instanceof Yn?r._expr:r}function Y(r){if((r=Rp(r))instanceof $r)return new c9(r);if(r instanceof Gr)return new l9(r);if(r instanceof Ai)return new h9(r);if(r instanceof O){if(r.name==="add")return new p9(r);if(r.name==="subtract")return new g9(r);if(r.name==="multiply")return new m9(r);if(r.name==="divide")return new _9(r);if(r.name==="mod")return new y9(r);if(r.name==="and")return new E9(r);if(r.name==="equal")return new V9(r);if(r.name==="not_equal")return new x9(r);if(r.name==="less_than")return new D9(r);if(r.name==="less_than_or_equal")return new O9(r);if(r.name==="greater_than")return new k9(r);if(r.name==="greater_than_or_equal")return new L9(r);if(r.name==="array_concat")return new M9(r);if(r.name==="array_reverse")return new F9(r);if(r.name==="array_contains")return new U9(r);if(r.name==="array_contains_all")return new B9(r);if(r.name==="array_contains_any")return new q9(r);if(r.name==="array_length")return new $9(r);if(r.name==="array_element")return new G9(r);if(r.name==="equal_any")return new Sp(r);if(r.name==="not_equal_any")return new w9(r);if(r.name==="is_nan")return new T9(r);if(r.name==="is_not_nan")return new A9(r);if(r.name==="is_null")return new v9(r);if(r.name==="is_not_null")return new R9(r);if(r.name==="is_error")return new S9(r);if(r.name==="exists")return new P9(r);if(r.name==="not")return new mu(r);if(r.name==="or")return new I9(r);if(r.name==="xor")return new K1(r);if(r.name==="conditional")return new b9(r);if(r.name==="maximum")return new C9(r);if(r.name==="minimum")return new N9(r);if(r.name==="reverse")return new j9(r);if(r.name==="replace_first")return new z9(r);if(r.name==="replace_all")return new H9(r);if(r.name==="char_length")return new K9(r);if(r.name==="byte_length")return new W9(r);if(r.name==="like")return new Q9(r);if(r.name==="regex_contains")return new Y9(r);if(r.name==="regex_match")return new X9(r);if(r.name==="string_contains")return new J9(r);if(r.name==="starts_with")return new Z9(r);if(r.name==="ends_with")return new e8(r);if(r.name==="to_lower")return new t8(r);if(r.name==="to_upper")return new n8(r);if(r.name==="trim")return new r8(r);if(r.name==="string_concat")return new s8(r);if(r.name==="map_get")return new i8(r);if(r.name==="cosine_distance")return new o8(r);if(r.name==="dot_product")return new a8(r);if(r.name==="euclidean_distance")return new u8(r);if(r.name==="vector_length")return new c8(r);if(r.name==="unix_micros_to_timestamp")return new p8(r);if(r.name==="timestamp_to_unix_micros")return new _8(r);if(r.name==="unix_millis_to_timestamp")return new g8(r);if(r.name==="timestamp_to_unix_millis")return new y8(r);if(r.name==="unix_seconds_to_timestamp")return new m8(r);if(r.name==="timestamp_to_unix_seconds")return new E8(r);if(r.name==="timestamp_add")return new I8(r);if(r.name==="timestamp_subtract")return new w8(r)}throw new Error(`Unknown Expr : ${r}`)}class c9{constructor(e){this.expr=e}evaluate(e,t){if(this.expr.fieldName===$t)return T.newValue({referenceValue:Ss(e.serializer,t.key)});if(this.expr.fieldName==="__update_time__")return T.newValue({timestampValue:fa(e.serializer,t.version)});if(this.expr.fieldName==="__create_time__")return T.newValue({timestampValue:fa(e.serializer,t.createTime)});const n=t.data.field(this.expr._fieldPath);return n?au(n)?T.newValue(function(i,o){if(i.serverTimestampBehavior==="estimate")return{timestampValue:fa(i.serializer,K.fromTimestamp(ys(o)))};if(i.serverTimestampBehavior==="previous"){const u=_o(o);if(u)return u}return{nullValue:"NULL_VALUE"}}(e,n)):T.newValue(n):T.Sr()}}class l9{constructor(e){this.expr=e}evaluate(e,t){return T.newValue(this.expr._getValue())}}class h9{constructor(e){this.expr=e}evaluate(e,t){const n=this.expr.Rr.map(s=>Y(s).evaluate(e,t));return n.some(s=>s.Cr())?T.vr():T.newValue({arrayValue:{values:n.map(s=>s.value)}})}}function Qe(r){return wr(r)?Number(r.doubleValue):Number(r.integerValue)}function Xt(r){return BigInt(r.integerValue)}const d9=BigInt("0x7fffffffffffffff"),f9=-BigInt("0x8000000000000000");class Ro{constructor(e){this.expr=e}evaluate(e,t){U(this.expr.params.length>=2,24778);const n=Y(this.expr.params[0]).evaluate(e,t),s=Y(this.expr.params[1]).evaluate(e,t);let i=this.Or(n,s);for(const o of this.expr.params.slice(2)){const u=Y(o).evaluate(e,t);i=this.Or(i,u)}return i}Or(e,t){if(e.Cr()||t.Cr())return T.vr();if(e.Fr()||t.Fr())return T.Dr();const n=e.value,s=t.value;if(!wr(n)&&!jt(n)||!wr(s)&&!jt(s))return T.vr();if(wr(n)||wr(s)){const i=this.Mr(n,s);return i?T.newValue(i):T.vr()}if(jt(n)&&jt(s)){const i=this.Nr(n,s);return i===void 0?T.vr():typeof i=="number"?T.newValue({doubleValue:i}):i<f9||i>d9?T.vr():T.newValue({integerValue:`${i}`})}return T.vr()}}function pn(r,e){return Fe(r)!==Fe(e)?"TYPE_MISMATCH":gt(r)||gt(e)?"NOT_EQ":It(r)&&It(e)?"EQ":It(r)||It(e)?"NULL":Wn(r)&&Wn(e)?function(n,s){var o,u,c;if(((o=n.values)==null?void 0:o.length)!==((u=s.values)==null?void 0:u.length))return"NOT_EQ";let i=!1;for(let l=0;l<(((c=n.values)==null?void 0:c.length)??0);l++){const d=n.values[l],g=s.values[l];switch(pn(d,g)){case"EQ":break;case"NOT_EQ":case"TYPE_MISMATCH":return"NOT_EQ";case"NULL":i=!0;break;default:j(44609,{Lr:d,Br:g})}}return i?"NULL":"EQ"}(r.arrayValue,e.arrayValue):Or(r)&&Or(e)||Sr(r)&&Sr(e)?function(n,s){const i=n.fields||{},o=s.fields||{};if(ba(i)!==ba(o))return"NOT_EQ";let u=!1;for(const c in i)if(i.hasOwnProperty(c)){if(o[c]===void 0)return"NOT_EQ";switch(pn(i[c],o[c])){case"NOT_EQ":case"TYPE_MISMATCH":return"NOT_EQ";case"NULL":u=!0}}return u?"NULL":"EQ"}(r.mapValue,e.mapValue):function(n,s){return St(n,s,{Te:!1,Ee:!0,he:!0})}(r,e)?"EQ":"NOT_EQ"}class p9 extends Ro{Nr(e,t){return Xt(e)+Xt(t)}Mr(e,t){return{doubleValue:Qe(e)+Qe(t)}}}class g9 extends Ro{constructor(e){super(e),this.expr=e}Nr(e,t){return Xt(e)-Xt(t)}Mr(e,t){return{doubleValue:Qe(e)-Qe(t)}}}class m9 extends Ro{constructor(e){super(e),this.expr=e}Nr(e,t){return Xt(e)*Xt(t)}Mr(e,t){return{doubleValue:Qe(e)*Qe(t)}}}class _9 extends Ro{constructor(e){super(e),this.expr=e}Nr(e,t){const n=Xt(t);if(n!==BigInt(0))return Xt(e)/n}Mr(e,t){const n=Qe(t);return n===0?{doubleValue:fs(n)?Number.NEGATIVE_INFINITY:Number.POSITIVE_INFINITY}:{doubleValue:Qe(e)/n}}}class y9 extends Ro{constructor(e){super(e),this.expr=e}Nr(e,t){const n=Xt(t);if(n!==BigInt(0))return Xt(e)%n}Mr(e,t){const n=Qe(t);if(n!==0)return{doubleValue:Qe(e)%n}}}class E9{constructor(e){this.expr=e}evaluate(e,t){var i;let n=!1,s=!1;for(const o of this.expr.params){const u=Y(o).evaluate(e,t);switch(u.type){case"BOOLEAN":if(!((i=u.value)!=null&&i.booleanValue))return T.newValue(He);break;case"NULL":s=!0;break;default:n=!0}}return n?T.vr():s?T.Dr():T.newValue(pt)}}class mu{constructor(e){this.expr=e}evaluate(e,t){var s;U(this.expr.params.length===1,9634);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"BOOLEAN":return T.newValue({booleanValue:!((s=n.value)!=null&&s.booleanValue)});case"NULL":return T.Dr();default:return T.vr()}}}class I9{constructor(e){this.expr=e}evaluate(e,t){var i;let n=!1,s=!1;for(const o of this.expr.params){const u=Y(o).evaluate(e,t);switch(u.type){case"BOOLEAN":if((i=u.value)!=null&&i.booleanValue)return T.newValue(pt);break;case"NULL":s=!0;break;default:n=!0}}return n?T.vr():s?T.Dr():T.newValue(He)}}class K1{constructor(e){this.expr=e}evaluate(e,t){var i;let n=!1,s=!1;for(const o of this.expr.params){const u=Y(o).evaluate(e,t);switch(u.type){case"BOOLEAN":n=K1.xor(n,!!((i=u.value)!=null&&i.booleanValue));break;case"NULL":s=!0;break;default:return T.vr()}}return s?T.Dr():T.newValue({booleanValue:n})}static xor(e,t){return(e||t)&&!(e&&t)}}class Sp{constructor(e){this.expr=e}evaluate(e,t){var o,u;U(this.expr.params.length===2,55094);let n=!1;const s=Y(this.expr.params[0]).evaluate(e,t);switch(s.type){case"NULL":n=!0;break;case"ERROR":case"UNSET":return T.vr()}const i=Y(this.expr.params[1]).evaluate(e,t);switch(i.type){case"ARRAY":break;case"NULL":n=!0;break;default:return T.vr()}if(n)return T.Dr();for(const c of((u=(o=i.value)==null?void 0:o.arrayValue)==null?void 0:u.values)??[])switch(It(s.value)&&It(c)?"EQ":pn(s.value,c)){case"EQ":return T.newValue(pt);case"NOT_EQ":case"TYPE_MISMATCH":break;case"NULL":n=!0;break;default:j(44608,{value:s.value,candidate:c})}return n?T.Dr():T.newValue(He)}}class w9{constructor(e){this.expr=e}evaluate(e,t){return new mu(new O("not",[new O("equal_any",this.expr.params)])).evaluate(e,t)}}class T9{constructor(e){this.expr=e}evaluate(e,t){U(this.expr.params.length===1,23322);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"INT":return T.newValue(He);case"DOUBLE":return T.newValue({booleanValue:isNaN(Qe(n.value))});case"NULL":return T.Dr();default:return T.vr()}}}class A9{constructor(e){this.expr=e}evaluate(e,t){return U(this.expr.params.length===1,50406),new mu(new O("not",[new O("is_nan",this.expr.params)])).evaluate(e,t)}}class v9{constructor(e){this.expr=e}evaluate(e,t){switch(U(this.expr.params.length===1,23123),Y(this.expr.params[0]).evaluate(e,t).type){case"NULL":return T.newValue(pt);case"UNSET":case"ERROR":return T.vr();default:return T.newValue(He)}}}class R9{constructor(e){this.expr=e}evaluate(e,t){return U(this.expr.params.length===1,23167),new mu(new O("not",[new O("is_null",this.expr.params)])).evaluate(e,t)}}class S9{constructor(e){this.expr=e}evaluate(e,t){return U(this.expr.params.length===1,5228),Y(this.expr.params[0]).evaluate(e,t).type==="ERROR"?T.newValue(pt):T.newValue(He)}}class P9{constructor(e){this.expr=e}evaluate(e,t){switch(U(this.expr.params.length===1,6877),Y(this.expr.params[0]).evaluate(e,t).type){case"ERROR":return T.vr();case"UNSET":return T.newValue(He);default:return T.newValue(pt)}}}class b9{constructor(e){this.expr=e}evaluate(e,t){var s;U(this.expr.params.length===3,11706);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"BOOLEAN":return(s=n.value)!=null&&s.booleanValue?Y(this.expr.params[1]).evaluate(e,t):Y(this.expr.params[2]).evaluate(e,t);case"NULL":return Y(this.expr.params[2]).evaluate(e,t);default:return T.vr()}}}class C9{constructor(e){this.expr=e}evaluate(e,t){const n=this.expr.params.map(i=>Y(i).evaluate(e,t));let s;for(const i of n)switch(i.type){case"ERROR":case"UNSET":case"NULL":continue;default:s=s===void 0||st(i.value,s.value)>0?i:s}return s===void 0?T.Dr():s}}class N9{constructor(e){this.expr=e}evaluate(e,t){const n=this.expr.params.map(i=>Y(i).evaluate(e,t));let s;for(const i of n)switch(i.type){case"ERROR":case"UNSET":case"NULL":continue;default:s=s===void 0||st(i.value,s.value)<0?i:s}return s===void 0?T.Dr():s}}class Gs{constructor(e){this.expr=e}evaluate(e,t){U(this.expr.params.length===2,31033,`${this.expr.name}() function should have exactly 2 params`);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"ERROR":case"UNSET":return T.vr()}const s=Y(this.expr.params[1]).evaluate(e,t);switch(s.type){case"ERROR":case"UNSET":return T.vr()}return this.Ur(n,s)}}class V9 extends Gs{constructor(e){super(e),this.expr=e}Ur(e,t){if(e.Fr()&&t.Fr())return T.newValue(pt);if(e.Fr()||t.Fr()||gt(e.value)||gt(t.value)||Fe(e.value)!==Fe(t.value))return T.newValue(He);switch(pn(e.value,t.value)){case"EQ":return T.newValue(pt);case"NOT_EQ":return T.newValue(He);case"NULL":return T.Dr();default:j(44615,{left:e,right:t})}}}class x9 extends Gs{constructor(e){super(e),this.expr=e}Ur(e,t){switch(pn(e.value,t.value)){case"EQ":return T.newValue(He);case"NOT_EQ":case"TYPE_MISMATCH":return T.newValue(pt);case"NULL":return T.Dr();default:j(44614,{left:e,right:t})}}}class D9 extends Gs{constructor(e){super(e),this.expr=e}Ur(e,t){return Fe(e.value)!==Fe(t.value)||gt(e.value)||gt(t.value)?T.newValue(He):T.newValue({booleanValue:st(e.value,t.value)<0})}}class O9 extends Gs{constructor(e){super(e),this.expr=e}Ur(e,t){return Fe(e.value)!==Fe(t.value)||gt(e.value)||gt(t.value)?T.newValue(He):pn(e.value,t.value)==="EQ"?T.newValue(pt):T.newValue({booleanValue:st(e.value,t.value)<0})}}class k9 extends Gs{constructor(e){super(e),this.expr=e}Ur(e,t){return Fe(e.value)!==Fe(t.value)||gt(e.value)||gt(t.value)?T.newValue(He):T.newValue({booleanValue:st(e.value,t.value)>0})}}class L9 extends Gs{constructor(e){super(e),this.expr=e}Ur(e,t){return Fe(e.value)!==Fe(t.value)||gt(e.value)||gt(t.value)?T.newValue(He):pn(e.value,t.value)==="EQ"?T.newValue(pt):T.newValue({booleanValue:st(e.value,t.value)>0})}}class M9{constructor(e){this.expr=e}evaluate(e,t){throw new Error("Unimplemented")}}class F9{constructor(e){this.expr=e}evaluate(e,t){var s;U(this.expr.params.length===1,216);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"NULL":return T.Dr();case"ARRAY":{const i=((s=n.value.arrayValue)==null?void 0:s.values)??[];return T.newValue({arrayValue:{values:[...i].reverse()}})}default:return T.vr()}}}class U9{constructor(e){this.expr=e}evaluate(e,t){return U(this.expr.params.length===2,52884),new Sp(new O("eq_any",[this.expr.params[1],this.expr.params[0]])).evaluate(e,t)}}class B9{constructor(e){this.expr=e}evaluate(e,t){var c,l,d,g;U(this.expr.params.length===2,1392);let n=!1;const s=Y(this.expr.params[0]).evaluate(e,t);switch(s.type){case"ARRAY":break;case"NULL":n=!0;break;default:return T.vr()}const i=Y(this.expr.params[1]).evaluate(e,t);switch(i.type){case"ARRAY":break;case"NULL":n=!0;break;default:return T.vr()}if(n)return T.Dr();const o=((l=(c=i.value)==null?void 0:c.arrayValue)==null?void 0:l.values)??[],u=((g=(d=s.value)==null?void 0:d.arrayValue)==null?void 0:g.values)??[];for(const y of o){let R=!1;n=!1;for(const C of u){switch(It(y)&&It(C)?"EQ":pn(y,C)){case"EQ":R=!0;break;case"NOT_EQ":case"TYPE_MISMATCH":break;case"NULL":n=!0;break;default:j(44613,{value:C,search:y})}if(R)break}if(!R)return T.newValue(He)}return T.newValue(pt)}}class q9{constructor(e){this.expr=e}evaluate(e,t){var c,l,d,g;U(this.expr.params.length===2,2680);let n=!1;const s=Y(this.expr.params[0]).evaluate(e,t);switch(s.type){case"ARRAY":break;case"NULL":n=!0;break;default:return T.vr()}const i=Y(this.expr.params[1]).evaluate(e,t);switch(i.type){case"ARRAY":break;case"NULL":n=!0;break;default:return T.vr()}if(n)return T.Dr();const o=((l=(c=i.value)==null?void 0:c.arrayValue)==null?void 0:l.values)??[],u=((g=(d=s.value)==null?void 0:d.arrayValue)==null?void 0:g.values)??[];for(const y of u)for(const R of o)switch(It(y)&&It(R)?"EQ":pn(y,R)){case"EQ":return T.newValue(pt);case"NOT_EQ":case"TYPE_MISMATCH":break;case"NULL":n=!0;break;default:j(44608,{value:y,search:R})}return n?T.Dr():T.newValue(He)}}class $9{constructor(e){this.expr=e}evaluate(e,t){var s,i,o;U(this.expr.params.length===1,38605);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"NULL":return T.Dr();case"ARRAY":return T.newValue({integerValue:`${((o=(i=(s=n.value)==null?void 0:s.arrayValue)==null?void 0:i.values)==null?void 0:o.length)??0}`});default:return T.vr()}}}class G9{constructor(e){this.expr=e}evaluate(e,t){throw new Error("Unimplemented")}}class j9{constructor(e){this.expr=e}evaluate(e,t){var s,i;U(this.expr.params.length===1,1508);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"NULL":return T.Dr();case"BYTES":{const o=(s=n.value)==null?void 0:s.bytesValue;if(typeof o=="string"){const u=Ce.fromBase64String(o).toUint8Array();return u.reverse(),T.newValue({bytesValue:Ce.fromUint8Array(u).toBase64()})}return T.newValue({bytesValue:new Uint8Array(o).reverse()})}case"STRING":{const o=(i=n.value)==null?void 0:i.stringValue,u=new Intl.__PRIVATE_Segmenter(void 0,{granularity:"grapheme"}).segment(o),c=Array.from(u,l=>l.segment).reverse();return T.newValue({stringValue:c.join("")})}default:return T.vr()}}}class z9{constructor(e){this.expr=e}evaluate(e,t){throw new Error("Unimplemented")}}class H9{constructor(e){this.expr=e}evaluate(e,t){throw new Error("Unimplemented")}}class K9{constructor(e){this.expr=e}evaluate(e,t){U(this.expr.params.length===1,19400);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"NULL":return T.Dr();case"STRING":{const s=function(o){let u=0;for(let c=0;c<o.length;c++){const l=o.codePointAt(c);if(l===void 0)return;if(l<=65535)if(l>=55296&&l<=57343)if(l<=56319){const d=o.codePointAt(c+1);d!==void 0&&d>=56320&&d<=57343?(u+=1,c++):u+=1}else u+=1;else u+=1;else{if(!(l<=1114111))return;u+=1,c++}}return u}(n.value.stringValue);return s===void 0?T.vr():T.newValue({integerValue:s})}default:return T.vr()}}}class W9{constructor(e){this.expr=e}evaluate(e,t){var s,i;U(this.expr.params.length===1,8486);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"BYTES":{const o=(s=n.value)==null?void 0:s.bytesValue;return typeof o=="string"?T.newValue({integerValue:Ce.fromBase64String(o).toUint8Array().length}):T.newValue({integerValue:new Uint8Array(o).length})}case"STRING":{const o=function(c){let l=0;for(let d=0;d<c.length;d++){const g=c.codePointAt(d);if(g===void 0)return;if(g>=55296&&g<=57343){if(!(g<=56319))return;{const y=c.codePointAt(d+1);if(y===void 0||!(y>=56320&&y<=57343))return;l+=4,d++}}else if(g<=127)l+=1;else if(g<=2047)l+=2;else if(g<=65535)l+=3;else{if(!(g<=1114111))return;l+=4,d++}}return l}((i=n.value)==null?void 0:i.stringValue);return o===void 0?T.vr():T.newValue({integerValue:o})}case"NULL":return T.Dr();default:return T.vr()}}}class js{constructor(e){this.expr=e}evaluate(e,t){var o,u;U(this.expr.params.length===2,39773,`${this.expr.name}() function should have exactly two parameters`);let n=!1;const s=Y(this.expr.params[0]).evaluate(e,t);switch(s.type){case"STRING":break;case"NULL":n=!0;break;default:return T.vr()}const i=Y(this.expr.params[1]).evaluate(e,t);switch(i.type){case"STRING":break;case"NULL":n=!0;break;default:return T.vr()}return n?T.Dr():this.kr((o=s.value)==null?void 0:o.stringValue,(u=i.value)==null?void 0:u.stringValue)}}class Q9 extends js{kr(e,t){try{const n=function(o){let u="";for(let c=0;c<o.length;c++){const l=o.charAt(c);switch(l){case"_":u+=".";break;case"%":u+=".*";break;case"\\":case".":case"*":case"?":case"+":case"^":case"$":case"|":case"(":case")":case"[":case"]":case"{":case"}":u+="\\"+l;break;default:u+=l}}return"^"+u+"$"}(t),s=Gi.compile(n);return T.newValue({booleanValue:s.matches(e)})}catch(n){return Lt(`Invalid LIKE pattern converted to regex: ${t}, returning error. Error: ${n}`),T.vr()}}}class Y9 extends js{kr(e,t){try{const n=Gi.compile(t);return T.newValue({booleanValue:n.matcher(e).find()})}catch{return Lt(`Invalid regex pattern found in regex_contains: ${t}, returning error`),T.vr()}}}class X9 extends js{kr(e,t){try{return T.newValue({booleanValue:Gi.compile(t).matches(e)})}catch{return Lt(`Invalid regex pattern found in regex_match: ${t}, returning error`),T.vr()}}}class J9 extends js{kr(e,t){return T.newValue({booleanValue:e.includes(t)})}}class Z9 extends js{kr(e,t){return T.newValue({booleanValue:e.startsWith(t)})}}class e8 extends js{kr(e,t){return T.newValue({booleanValue:e.endsWith(t)})}}class t8{constructor(e){this.expr=e}evaluate(e,t){var s,i;U(this.expr.params.length===1,29079);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"STRING":return T.newValue({stringValue:(i=(s=n.value)==null?void 0:s.stringValue)==null?void 0:i.toLowerCase()});case"NULL":return T.Dr();default:return T.vr()}}}class n8{constructor(e){this.expr=e}evaluate(e,t){var s,i;U(this.expr.params.length===1,60487);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"STRING":return T.newValue({stringValue:(i=(s=n.value)==null?void 0:s.stringValue)==null?void 0:i.toUpperCase()});case"NULL":return T.Dr();default:return T.vr()}}}class r8{constructor(e){this.expr=e}evaluate(e,t){var s,i;U(this.expr.params.length===1,28544);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"STRING":return T.newValue({stringValue:(i=(s=n.value)==null?void 0:s.stringValue)==null?void 0:i.trim()});case"NULL":return T.Dr();default:return T.vr()}}}class s8{constructor(e){this.expr=e}evaluate(e,t){const n=this.expr.params.map(o=>Y(o).evaluate(e,t));let s="",i=!1;for(const o of n)switch(o.type){case"STRING":s+=o.value.stringValue;break;case"NULL":i=!0;break;default:return T.vr()}return i?T.Dr():T.newValue({stringValue:s})}}class i8{constructor(e){this.expr=e}evaluate(e,t){var o,u,c,l;U(this.expr.params.length===2,4483);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"UNSET":return T.Sr();case"MAP":break;default:return T.vr()}const s=Y(this.expr.params[1]).evaluate(e,t);if(s.type!=="STRING")return T.vr();const i=(l=(u=(o=n.value)==null?void 0:o.mapValue)==null?void 0:u.fields)==null?void 0:l[(c=s.value)==null?void 0:c.stringValue];return i===void 0?T.Sr():T.newValue(i)}}class W1{constructor(e){this.expr=e}evaluate(e,t){var l,d;U(this.expr.params.length===2,25231,`${this.expr.name}() function should have exactly 2 params`);let n=!1;const s=Y(this.expr.params[0]).evaluate(e,t);switch(s.type){case"VECTOR":break;case"NULL":n=!0;break;default:return T.vr()}const i=Y(this.expr.params[1]).evaluate(e,t);switch(i.type){case"VECTOR":break;case"NULL":n=!0;break;default:return T.vr()}if(n)return T.Dr();const o=Fc(s.value),u=Fc(i.value);if(o===void 0||u===void 0||((l=o.values)==null?void 0:l.length)!==((d=u.values)==null?void 0:d.length))return T.vr();const c=this.qr(o,u);return c===void 0||isNaN(c)?T.vr():T.newValue({doubleValue:c})}}class o8 extends W1{qr(e,t){const n=(e==null?void 0:e.values)??[],s=(t==null?void 0:t.values)??[];if(n.length===0)return;let i=0,o=0,u=0;for(let l=0;l<n.length;l++){if(!Kn(n[l])||!Kn(s[l]))return;const d=Qe(n[l]),g=Qe(s[l]);i+=d*g,o+=d*d,u+=g*g}const c=Math.sqrt(o)*Math.sqrt(u);if(c!==0)return 1-Math.max(-1,Math.min(1,i/c))}}class a8 extends W1{qr(e,t){const n=(e==null?void 0:e.values)??[],s=(t==null?void 0:t.values)??[];if(n.length===0)return 0;let i=0;for(let o=0;o<n.length;o++){if(!Kn(n[o])||!Kn(s[o]))return;i+=Qe(n[o])*Qe(s[o])}return i}}class u8 extends W1{qr(e,t){const n=(e==null?void 0:e.values)??[],s=(t==null?void 0:t.values)??[];if(n.length===0)return 0;let i=0;for(let o=0;o<n.length;o++){if(!Kn(n[o])||!Kn(s[o]))return;const u=Qe(n[o]),c=Qe(s[o]);i+=Math.pow(u-c,2)}return Math.sqrt(i)}}class c8{constructor(e){this.expr=e}evaluate(e,t){var s;U(this.expr.params.length===1,39044);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"VECTOR":{const i=Fc(n.value);return T.newValue({integerValue:((s=i==null?void 0:i.values)==null?void 0:s.length)??0})}case"NULL":return T.Dr();default:return T.vr()}}}const ro=BigInt(-62135596800),so=BigInt(253402300799),ka=BigInt(1e3),jn=BigInt(1e6),l8=ro*ka,h8=so*ka+BigInt(999),d8=ro*jn,f8=so*jn+BigInt(999999);function Q1(r){return r>=d8&&r<=f8}function Pp(r){return r>=ro&&r<=so}function io(r,e){const t=BigInt(r);return!(t<ro||t>so)&&!(e<0||e>=1e9)&&(t!==ro||e===0)&&!(t===so&&e>999999999)}function bp(r,e){return e<0?{seconds:r-1,nanos:e+1e9}:{seconds:r,nanos:e}}function Y1(r){return BigInt(r.seconds)*jn+BigInt(Math.trunc(r.nanoseconds/1e3))}class X1{constructor(e){this.expr=e}evaluate(e,t){U(this.expr.params.length===1,49262,`${this.expr.name}() function should have exactly one parameter`);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"INT":return this.toTimestamp(BigInt(n.value.integerValue));case"NULL":return T.Dr();default:return T.vr()}}}class p8 extends X1{toTimestamp(e){if(!Q1(e))return T.vr();let t=Number(e/jn),n=Number(e%jn*BigInt(1e3));const s=bp(t,n);return t=s.seconds,n=s.nanos,io(t,n)?T.newValue({timestampValue:{seconds:t,nanos:n}}):T.vr()}}class g8 extends X1{toTimestamp(e){if(!function(o){return o>=l8&&o<=h8}(e))return T.vr();let t=Number(e/ka),n=Number(e%ka*BigInt(1e6));const s=bp(t,n);return t=s.seconds,n=s.nanos,io(t,n)?T.newValue({timestampValue:{seconds:t,nanos:n}}):T.vr()}}class m8 extends X1{toTimestamp(e){if(!Pp(e))return T.vr();const t=Number(e);return T.newValue({timestampValue:{seconds:t,nanos:0}})}}class J1{constructor(e){this.expr=e}evaluate(e,t){U(this.expr.params.length===1,1265,`${this.expr.name}() function should have exactly one parameter`);const n=Y(this.expr.params[0]).evaluate(e,t);switch(n.type){case"TIMESTAMP":break;case"NULL":return T.Dr();default:return T.vr()}const s=O1(n.value.timestampValue);return io(s.seconds,s.nanoseconds)?this.$r(s):T.vr()}}class _8 extends J1{$r(e){const t=Y1(e);return Q1(t)?T.newValue({integerValue:`${t.toString()}`}):T.vr()}}class y8 extends J1{$r(e){const t=Y1(e),n=t/BigInt(1e3),s=t%BigInt(1e3);return n>BigInt(0)||s===BigInt(0)?T.newValue({integerValue:n.toString()}):T.newValue({integerValue:(n-BigInt(1)).toString()})}}class E8 extends J1{$r(e){const t=BigInt(e.seconds);return Pp(t)?T.newValue({integerValue:t.toString()}):T.vr()}}class Cp{constructor(e){this.expr=e}evaluate(e,t){U(this.expr.params.length===3,2775,`${this.expr.name}() function should have exactly 3 parameters`);let n=!1;const s=Y(this.expr.params[0]).evaluate(e,t);switch(s.type){case"TIMESTAMP":break;case"NULL":n=!0;break;default:return T.vr()}const i=Y(this.expr.params[1]).evaluate(e,t);let o;switch(i.type){case"STRING":if(o=function(te){switch(te){case"microsecond":return"microsecond";case"millisecond":return"millisecond";case"second":return"second";case"minute":return"minute";case"hour":return"hour";case"day":return"day";default:return}}(i.value.stringValue),o===void 0)return T.vr();break;case"NULL":n=!0;break;default:return T.vr()}const u=Y(this.expr.params[2]).evaluate(e,t);switch(u.type){case"INT":break;case"NULL":n=!0;break;default:return T.vr()}if(n)return T.Dr();const c=BigInt(u.value.integerValue);let l;try{switch(o){case"microsecond":l=c;break;case"millisecond":l=c*BigInt(1e3);break;case"second":l=c*BigInt(1e6);break;case"minute":l=c*BigInt(6e7);break;case"hour":l=c*BigInt(36e8);break;case"day":l=c*BigInt(864e8);break;default:return T.vr()}if(o!=="microsecond"&&c!==BigInt(0)&&l/c!==BigInt(this.Kr(o)))return T.vr()}catch(Q){return Lt(`Error during timestamp arithmetic: ${Q}`),T.vr()}const d=O1(s.value.timestampValue);if(!io(d.seconds,d.nanoseconds))return T.vr();const g=Y1(d),y=this.Wr(g,l);if(!Q1(y))return T.vr();const R=Number(y/jn),C=y%jn,M=Number((C<0?C+jn:C)*BigInt(1e3)),q=C<0?R-1:R;return io(q,M)?T.newValue({timestampValue:{seconds:q,nanos:M}}):T.vr()}Kr(e){switch(e){case"millisecond":return 1e3;case"second":return 1e6;case"minute":return 6e7;case"hour":return 36e8;case"day":return 864e8;default:return 1}}}class I8 extends Cp{Wr(e,t){return e+t}}class w8 extends Cp{Wr(e,t){return e-t}}function oo(r){if((r=Rp(r))instanceof $r)return`fld(${r.fieldName})`;if(r instanceof Gr)return`cst(${function(t){return t===null?"null":typeof t=="number"?t.toString():typeof t=="string"?`"${t}"`:t instanceof Pe?`ref(${t.path})`:t instanceof ft?`vec(${JSON.stringify(t)})`:JSON.stringify(t)}(r.value)})`;if(r instanceof O)return`fn(${r.name},[${r.params.map(oo).join(",")}])`;if(r.expressionType==="ListOfExpressions")return`list([${r.Rr.map(oo).join(",")}])`;throw new Error(`Unrecognized expr ${JSON.stringify(r,null,2)}`)}function T8(r){if(r instanceof wp)return`${r._name}(${Zo(r.fields)})`;if(r instanceof Tp){let e=`${r._name}(${Zo(r.accumulators)})`;return r.groups.size>0&&(e+=`grouping(${Zo(r.groups)})`),e}if(r instanceof Ap)return`${r._name}(${Zo(r.groups)})`;if(r instanceof wo)return`${r._name}(${r.Vr})`;if(r instanceof To)return`${r._name}(${r.collectionId})`;if(r instanceof pu)return`${r._name}()`;if(r instanceof gu)return`${r._name}(${r.dr.sort()})`;if(r instanceof Ao)return`${r._name}(${oo(r.condition)})`;if(r instanceof Xn)return`${r._name}(${r.limit})`;if(r instanceof zt)return`${r._name}(${function(t){return t.map(n=>`${oo(n.expr)}${n.direction}`).join(",")}(r.orderings)})`;throw new Error(`Unrecognized stage ${r._name}`)}function Zo(r){return`${Array.from(r.entries()).sort().map(([e,t])=>`${e}=${oo(t)}`).join(",")}`}function cn(r){return r.stages.map(e=>T8(e)).join("|")}function Np(r,e){return cn(r)===cn(e)}function xe(r){return r instanceof nt}function df(r){return xe(r)?cn(r):Oi(r)}function Vp(r){return xe(r)?cn(r):function(t){return`${Va(wt(t))}|lt:${t.limitType}`}(r)}function _u(r,e){return r instanceof nt&&e instanceof nt?Np(r,e):!(r instanceof nt&&!(e instanceof nt)||!(r instanceof nt)&&e instanceof nt)&&a3(r,e)}function yu(r){return nn(r)?cn(r):Va(r)}function Z1(r,e){return r instanceof nt&&e instanceof nt?Np(r,e):!(r instanceof nt&&!(e instanceof nt)||!(r instanceof nt)&&e instanceof nt)&&C1(r,e)}function A8(r,e){const t=function(s){let i=!1;const o=[];for(const u of s)if(u instanceof zt)if(i=!0,u.orderings.some(c=>c.expr instanceof $r&&c.expr.fieldName===$t))o.push(u);else{const c=u.orderings.map(l=>l);c.push(pa($t).ascending()),o.push(new zt(c,{}))}else u instanceof Xn&&(i||(o.push(new zt([pa($t).ascending()],{})),i=!0)),o.push(u);return i||o.push(new zt([pa($t).ascending()],{})),o}(r.stages);if(r.userDataReader){const n=r.userDataReader.createContext(3,"toCorePipeline");t.forEach(s=>s._readUserData(n))}return new nt(r.userDataReader.serializer,t,e)}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class el{constructor(e,t,n,s){this.batchId=e,this.localWriteTime=t,this.baseMutations=n,this.mutations=s}applyToRemoteDocument(e,t){const n=t.mutationResults;for(let s=0;s<this.mutations.length;s++){const i=this.mutations[s];i.key.isEqual(e.key)&&W4(i,e,n[s])}}applyToLocalView(e,t){for(const n of this.baseMutations)n.key.isEqual(e.key)&&(t=xi(n,e,t,this.localWriteTime));for(const n of this.mutations)n.key.isEqual(e.key)&&(t=xi(n,e,t,this.localWriteTime));return t}applyToLocalDocumentSet(e,t){const n=$6();return this.mutations.forEach(s=>{const i=e.get(s.key),o=i.overlayedDocument;let u=this.applyToLocalView(o,i.mutatedFields);u=t.has(s.key)?null:u;const c=P6(o,u);c!==null&&n.set(s.key,c),o.isValidDocument()||o.convertToNoDocument(K.min())}),n}keys(){return this.mutations.reduce((e,t)=>e.add(t.key),se())}isEqual(e){return this.batchId===e.batchId&&hs(this.mutations,e.mutations,(t,n)=>$d(t,n))&&hs(this.baseMutations,e.baseMutations,(t,n)=>$d(t,n))}}class tl{constructor(e,t,n,s){this.batch=e,this.commitVersion=t,this.mutationResults=n,this.docVersions=s}static from(e,t,n){U(e.mutations.length===n.length,58842,{Qr:e.mutations.length,Gr:n.length});let s=function(){return h3}();const i=e.mutations;for(let o=0;o<i.length;o++)s=s.insert(i[o].key,n[o].version);return new tl(e,t,n,s)}}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class nl{constructor(e,t){this.largestBatchId=e,this.mutation=t}getKey(){return this.mutation.key}isEqual(e){return e!==null&&this.mutation===e.mutation}toString(){return`Overlay{
      largestBatchId: ${this.largestBatchId},
      mutation: ${this.mutation.toString()}
    }`}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Ht{constructor(e,t,n,s,i=K.min(),o=K.min(),u=Ce.EMPTY_BYTE_STRING,c=null){this.target=e,this.targetId=t,this.purpose=n,this.sequenceNumber=s,this.snapshotVersion=i,this.lastLimboFreeSnapshotVersion=o,this.resumeToken=u,this.expectedCount=c}withSequenceNumber(e){return new Ht(this.target,this.targetId,this.purpose,e,this.snapshotVersion,this.lastLimboFreeSnapshotVersion,this.resumeToken,this.expectedCount)}withResumeToken(e,t){return new Ht(this.target,this.targetId,this.purpose,this.sequenceNumber,t,this.lastLimboFreeSnapshotVersion,e,null)}withExpectedCount(e){return new Ht(this.target,this.targetId,this.purpose,this.sequenceNumber,this.snapshotVersion,this.lastLimboFreeSnapshotVersion,this.resumeToken,e)}withLastLimboFreeSnapshotVersion(e){return new Ht(this.target,this.targetId,this.purpose,this.sequenceNumber,this.snapshotVersion,e,this.resumeToken,this.expectedCount)}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class xp{constructor(e){this.zr=e}}function v8(r,e){let t;if(e.document)t=w3(r.zr,e.document,!!e.hasCommittedMutations);else if(e.noDocument){const n=$.fromSegments(e.noDocument.path),s=Lr(e.noDocument.readTime);t=Re.newNoDocument(n,s),e.hasCommittedMutations&&t.setHasCommittedMutations()}else{if(!e.unknownDocument)return j(56709);{const n=$.fromSegments(e.unknownDocument.path),s=Lr(e.unknownDocument.version);t=Re.newUnknownDocument(n,s)}}return e.readTime&&t.setReadTime(function(s){const i=new me(s[0],s[1]);return K.fromTimestamp(i)}(e.readTime)),t}function ff(r,e){const t=e.key,n={prefixPath:t.getCollectionPath().popLast().toArray(),collectionGroup:t.collectionGroup,documentId:t.path.lastSegment(),readTime:La(e.readTime),hasCommittedMutations:e.hasCommittedMutations};if(e.isFoundDocument())n.document=function(i,o){return{name:Ss(i,o.key),fields:o.data.value.mapValue.fields,updateTime:Rs(i,o.version.toTimestamp()),createTime:Rs(i,o.createTime.toTimestamp())}}(r.zr,e);else if(e.isNoDocument())n.noDocument={path:t.path.toArray(),readTime:kr(e.version)};else{if(!e.isUnknownDocument())return j(57904,{document:e});n.unknownDocument={path:t.path.toArray(),version:kr(e.version)}}return n}function La(r){const e=r.toTimestamp();return[e.seconds,e.nanoseconds]}function kr(r){const e=r.toTimestamp();return{seconds:e.seconds,nanoseconds:e.nanoseconds}}function Lr(r){const e=new me(r.seconds,r.nanoseconds);return K.fromTimestamp(e)}function _r(r,e){const t=(e.baseMutations||[]).map(i=>Kc(r.zr,i));for(let i=0;i<e.mutations.length-1;++i){const o=e.mutations[i];if(i+1<e.mutations.length&&e.mutations[i+1].transform!==void 0){const u=e.mutations[i+1];o.updateTransforms=u.transform.fieldTransforms,e.mutations.splice(i+1,1),++i}}const n=e.mutations.map(i=>Kc(r.zr,i)),s=me.fromMillis(e.localWriteTimeMs);return new el(e.batchId,s,t,n)}function vi(r,e){const t=Lr(e.readTime),n=e.lastLimboFreeSnapshotVersion!==void 0?Lr(e.lastLimboFreeSnapshotVersion):K.min();let s;return s=function(o){return o.structuredPipeline!==void 0}(e.query)?function(o,u){var d,g;const c=o.structuredPipeline;U((((d=c==null?void 0:c.pipeline)==null?void 0:d.stages)??[]).length>0,1845);const l=(g=c==null?void 0:c.pipeline)==null?void 0:g.stages.map(R8);return new nt(u,l)}(e.query,r.zr):function(o){return o.documents!==void 0}(e.query)?function(o){const u=o.documents.length;return U(u===1,1966,{count:u}),wt(yo(W6(o.documents[0])))}(e.query):function(o){return wt(J6(o))}(e.query),new Ht(s,e.targetId,"TargetPurposeListen",e.lastListenSequenceNumber,t,n,Ce.fromBase64String(e.resumeToken))}function Dp(r,e){const t=kr(e.snapshotVersion),n=kr(e.lastLimboFreeSnapshotVersion);let s;s=nn(e.target)?Z6(r.zr,e.target):N1(e.target)?Y6(r.zr,e.target):X6(r.zr,e.target).yt;const i=e.resumeToken.toBase64();return{targetId:e.targetId,canonicalId:yu(e.target),readTime:t,resumeToken:i,lastListenSequenceNumber:e.sequenceNumber,lastLimboFreeSnapshotVersion:n,query:s}}function Op(r){const e=J6({parent:r.parent,structuredQuery:r.structuredQuery});return r.limitType==="LAST"?Gc(e,e.limit,"L"):e}function ea(r,e){return new nl(e.largestBatchId,Kc(r.zr,e.overlayMutation))}function pf(r,e){const t=e.path.lastSegment();return[r,rt(e.path.popLast()),t]}function gf(r,e,t,n){return{indexId:r,uid:e,sequenceNumber:t,readTime:kr(n.readTime),documentKey:rt(n.documentKey.path),largestBatchId:n.largestBatchId}}function R8(r){switch(r.name){case"collection":return new wo(r.args[0].referenceValue,{});case"collection_group":return new To(r.args[1].stringValue,{});case"database":return new pu({});case"documents":return new gu(r.args.map(e=>e.referenceValue),{});case"where":return new Ao(Qc(r.args[0]),{});case"limit":{const e=r.args[0].integerValue??r.args[0].doubleValue;return new Xn(typeof e=="number"?e:Number(e),{})}case"sort":return new zt(r.args.map(e=>function(n){var i,o;const s=(i=n.mapValue)==null?void 0:i.fields;return new j1(Qc(s.expression),(o=s.direction)==null?void 0:o.stringValue,"orderingFromProto")}(e)),{});default:throw new Error(`Stage type: ${r.name} not supported.`)}}function Qc(r){return r.fieldReferenceValue?new $r(fn("_exprFromProto",r.fieldReferenceValue),"_exprFromProto"):r.functionValue?function(t){var n;return new O(t.functionValue.name,((n=t.functionValue.args)==null?void 0:n.map(Qc))||[])}(r):Gr._fromProto(r)}class S8{getBundleMetadata(e,t){return mf(e).get(t).next(n=>{if(n)return function(i){return{id:i.bundleId,createTime:Lr(i.createTime),version:i.version}}(n)})}saveBundleMetadata(e,t){return mf(e).put(function(s){return{bundleId:s.id,createTime:kr(Ke(s.createTime)),version:s.version}}(t))}getNamedQuery(e,t){return _f(e).get(t).next(n=>{if(n)return function(i){return{name:i.name,query:Op(i.bundledQuery),readTime:Lr(i.readTime)}}(n)})}saveNamedQuery(e,t){return _f(e).put(function(s){return{name:s.name,readTime:kr(Ke(s.readTime)),bundledQuery:s.bundledQuery}}(t))}}function mf(r){return $e(r,su)}function _f(r){return $e(r,iu)}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Eu{constructor(e,t){this.serializer=e,this.userId=t}static jr(e,t){const n=t.uid||"";return new Eu(e,n)}getOverlay(e,t){return Yr(e).get(pf(this.userId,t)).next(n=>n?ea(this.serializer,n):null)}getOverlays(e,t){const n=vt();return P.forEach(t,s=>this.getOverlay(e,s).next(i=>{i!==null&&n.set(s,i)})).next(()=>n)}getAllOverlays(e,t){const n=vt();return Yr(e).ee((s,i)=>{const o=ea(this.serializer,i);o.largestBatchId>t&&n.set(o.getKey(),o)}).next(()=>n)}saveOverlays(e,t,n){const s=[];return n.forEach((i,o)=>{const u=new nl(t,o);s.push(this.Hr(e,u))}),P.waitFor(s)}removeOverlaysForBatchId(e,t,n){const s=new Set;t.forEach(o=>s.add(rt(o.getCollectionPath())));const i=[];return s.forEach(o=>{const u=IDBKeyRange.bound([this.userId,o,n],[this.userId,o,n+1],!1,!0);i.push(Yr(e).Z(kc,u))}),P.waitFor(i)}getOverlaysForCollection(e,t,n){const s=vt(),i=rt(t),o=IDBKeyRange.bound([this.userId,i,n],[this.userId,i,Number.POSITIVE_INFINITY],!0);return Yr(e).H(kc,o).next(u=>{for(const c of u){const l=ea(this.serializer,c);s.set(l.getKey(),l)}return s})}getOverlaysForCollectionGroup(e,t,n,s){const i=vt();let o;const u=IDBKeyRange.bound([this.userId,t,n],[this.userId,t,Number.POSITIVE_INFINITY],!0);return Yr(e).ee({index:u6,range:u},(c,l,d)=>{const g=ea(this.serializer,l);i.size()<s||g.largestBatchId===o?(i.set(g.getKey(),g),o=g.largestBatchId):d.done()}).next(()=>i)}Hr(e,t){return Yr(e).put(function(s,i,o){const[u,c,l]=pf(i,o.mutation.key);return{userId:i,collectionPath:c,documentId:l,collectionGroup:o.mutation.key.getCollectionGroup(),largestBatchId:o.largestBatchId,overlayMutation:eo(s.zr,o.mutation)}}(this.serializer,this.userId,t))}}function Yr(r){return $e(r,ou)}/**
 * @license
 * Copyright 2024 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class P8{Jr(e){return $e(e,I1)}getSessionToken(e){return this.Jr(e).get("sessionToken").next(t=>{const n=t==null?void 0:t.value;return n?Ce.fromUint8Array(n):Ce.EMPTY_BYTE_STRING})}setSessionToken(e,t){return this.Jr(e).put({name:"sessionToken",value:t.toUint8Array()})}}/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class yr{constructor(){}Yr(e,t){this.Zr(e,t),t.Xr()}Zr(e,t){if("nullValue"in e)this.ei(t,5);else if("booleanValue"in e)this.ei(t,10),t.ti(e.booleanValue?1:0);else if("integerValue"in e)this.ei(t,15),t.ti(we(e.integerValue));else if("doubleValue"in e){const n=we(e.doubleValue);isNaN(n)?this.ei(t,13):(this.ei(t,15),fs(n)?t.ti(0):t.ti(n))}else if("timestampValue"in e){let n=e.timestampValue;this.ei(t,20),typeof n=="string"&&(n=hn(n)),t.ni(`${n.seconds||""}`),t.ti(n.nanos||0)}else if("stringValue"in e)this.ri(e.stringValue,t),this.ii(t);else if("bytesValue"in e)this.ei(t,30),t.si(dn(e.bytesValue)),this.ii(t);else if("referenceValue"in e)this._i(e.referenceValue,t);else if("geoPointValue"in e){const n=e.geoPointValue;this.ei(t,45),t.ti(n.latitude||0),t.ti(n.longitude||0)}else"mapValue"in e?I6(e)?this.ei(t,Number.MAX_SAFE_INTEGER):Or(e)?this.oi(e.mapValue,t):(this.ai(e.mapValue,t),this.ii(t)):"arrayValue"in e?(this.ui(e.arrayValue,t),this.ii(t)):j(19022,{ci:e})}ri(e,t){this.ei(t,25),this.li(e,t)}li(e,t){t.ni(e)}ai(e,t){const n=e.fields||{};this.ei(t,55);for(const s of Object.keys(n))this.ri(s,t),this.Zr(n[s],t)}oi(e,t){var o,u;const n=e.fields||{};this.ei(t,53);const s=Dr,i=((u=(o=n[s].arrayValue)==null?void 0:o.values)==null?void 0:u.length)||0;this.ei(t,15),t.ti(we(i)),this.ri(s,t),this.Zr(n[s],t)}ui(e,t){const n=e.values||[];this.ei(t,50);for(const s of n)this.Zr(s,t)}_i(e,t){this.ei(t,37),$.fromName(e).path.forEach(n=>{this.ei(t,60),this.li(n,t)})}ei(e,t){e.ti(t)}ii(e){e.ti(2)}}yr.Ei=new yr;/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law | agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES | CONDITIONS OF ANY KIND, either express | implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Xr=255;function b8(r){if(r===0)return 8;let e=0;return r>>4||(e+=4,r<<=4),r>>6||(e+=2,r<<=2),r>>7||(e+=1),e}function yf(r){const e=64-function(n){let s=0;for(let i=0;i<8;++i){const o=b8(255&n[i]);if(s+=o,o!==8)break}return s}(r);return Math.ceil(e/8)}class C8{constructor(){this.buffer=new Uint8Array(1024),this.position=0}hi(e){const t=e[Symbol.iterator]();let n=t.next();for(;!n.done;)this.Ti(n.value),n=t.next();this.Pi()}Ri(e){const t=e[Symbol.iterator]();let n=t.next();for(;!n.done;)this.Ii(n.value),n=t.next();this.Ai()}Vi(e){for(const t of e){const n=t.charCodeAt(0);if(n<128)this.Ti(n);else if(n<2048)this.Ti(960|n>>>6),this.Ti(128|63&n);else if(t<"\uD800"||"\uDBFF"<t)this.Ti(480|n>>>12),this.Ti(128|63&n>>>6),this.Ti(128|63&n);else{const s=t.codePointAt(0);this.Ti(240|s>>>18),this.Ti(128|63&s>>>12),this.Ti(128|63&s>>>6),this.Ti(128|63&s)}}this.Pi()}di(e){for(const t of e){const n=t.charCodeAt(0);if(n<128)this.Ii(n);else if(n<2048)this.Ii(960|n>>>6),this.Ii(128|63&n);else if(t<"\uD800"||"\uDBFF"<t)this.Ii(480|n>>>12),this.Ii(128|63&n>>>6),this.Ii(128|63&n);else{const s=t.codePointAt(0);this.Ii(240|s>>>18),this.Ii(128|63&s>>>12),this.Ii(128|63&s>>>6),this.Ii(128|63&s)}}this.Ai()}fi(e){const t=this.mi(e),n=yf(t);this.pi(1+n),this.buffer[this.position++]=255&n;for(let s=t.length-n;s<t.length;++s)this.buffer[this.position++]=255&t[s]}gi(e){const t=this.mi(e),n=yf(t);this.pi(1+n),this.buffer[this.position++]=~(255&n);for(let s=t.length-n;s<t.length;++s)this.buffer[this.position++]=~(255&t[s])}yi(){this.wi(Xr),this.wi(255)}bi(){this.Si(Xr),this.Si(255)}reset(){this.position=0}seed(e){this.pi(e.length),this.buffer.set(e,this.position),this.position+=e.length}Di(){return this.buffer.slice(0,this.position)}mi(e){const t=function(i){const o=new DataView(new ArrayBuffer(8));return o.setFloat64(0,i,!1),new Uint8Array(o.buffer)}(e),n=!!(128&t[0]);t[0]^=n?255:128;for(let s=1;s<t.length;++s)t[s]^=n?255:0;return t}Ti(e){const t=255&e;t===0?(this.wi(0),this.wi(255)):t===Xr?(this.wi(Xr),this.wi(0)):this.wi(t)}Ii(e){const t=255&e;t===0?(this.Si(0),this.Si(255)):t===Xr?(this.Si(Xr),this.Si(0)):this.Si(e)}Pi(){this.wi(0),this.wi(1)}Ai(){this.Si(0),this.Si(1)}wi(e){this.pi(1),this.buffer[this.position++]=e}Si(e){this.pi(1),this.buffer[this.position++]=~e}pi(e){const t=e+this.position;if(t<=this.buffer.length)return;let n=2*this.buffer.length;n<t&&(n=t);const s=new Uint8Array(n);s.set(this.buffer),this.buffer=s}}class N8{constructor(e){this.xi=e}si(e){this.xi.hi(e)}ni(e){this.xi.Vi(e)}ti(e){this.xi.fi(e)}Xr(){this.xi.yi()}}class V8{constructor(e){this.xi=e}si(e){this.xi.Ri(e)}ni(e){this.xi.di(e)}ti(e){this.xi.gi(e)}Xr(){this.xi.bi()}}class _i{constructor(){this.xi=new C8,this.ascending=new N8(this.xi),this.descending=new V8(this.xi)}seed(e){this.xi.seed(e)}Ci(e){return e===0?this.ascending:this.descending}Di(){return this.xi.Di()}reset(){this.xi.reset()}}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Er{constructor(e,t,n,s){this.Fi=e,this.Oi=t,this.Mi=n,this.Ni=s}Li(){const e=this.Ni.length,t=e===0||this.Ni[e-1]===255?e+1:e,n=new Uint8Array(t);return n.set(this.Ni,0),t!==e?n.set([0],this.Ni.length):++n[n.length-1],new Er(this.Fi,this.Oi,this.Mi,n)}Bi(e,t,n){return{indexId:this.Fi,uid:e,arrayValue:ga(this.Mi),directionalValue:ga(this.Ni),orderedDocumentKey:ga(t),documentKey:n.path.toArray()}}Ui(e,t,n){const s=this.Bi(e,t,n);return[s.indexId,s.uid,s.arrayValue,s.directionalValue,s.orderedDocumentKey,s.documentKey]}}function Sn(r,e){let t=r.Fi-e.Fi;return t!==0?t:(t=Ef(r.Mi,e.Mi),t!==0?t:(t=Ef(r.Ni,e.Ni),t!==0?t:$.comparator(r.Oi,e.Oi)))}function Ef(r,e){for(let t=0;t<r.length&&t<e.length;++t){const n=r[t]-e[t];if(n!==0)return n}return r.length-e.length}function ga(r){return D2()?function(t){let n="";for(let s=0;s<t.length;s++)n+=String.fromCharCode(t[s]);return n}(r):r}function If(r){return typeof r!="string"?r:function(t){const n=new Uint8Array(t.length);for(let s=0;s<t.length;s++)n[s]=t.charCodeAt(s);return n}(r)}class wf{constructor(e){this.ki=new ge((t,n)=>ve.comparator(t.field,n.field)),this.collectionId=e.collectionGroup!=null?e.collectionGroup:e.path.lastSegment(),this.qi=e.orderBy,this.$i=[];for(const t of e.filters){const n=t;n.isInequality()?this.ki=this.ki.add(n):this.$i.push(n)}}get Ki(){return this.ki.size>1}Wi(e){if(U(e.collectionGroup===this.collectionId,49279),this.Ki)return!1;const t=xc(e);if(t!==void 0&&!this.Qi(t))return!1;const n=fr(e);let s=new Set,i=0,o=0;for(;i<n.length&&this.Qi(n[i]);++i)s=s.add(n[i].fieldPath.canonicalString());if(i===n.length)return!0;if(this.ki.size>0){const u=this.ki.getIterator().getNext();if(!s.has(u.field.canonicalString())){const c=n[i];if(!this.Gi(u,c)||!this.zi(this.qi[o++],c))return!1}++i}for(;i<n.length;++i){const u=n[i];if(o>=this.qi.length||!this.zi(this.qi[o++],u))return!1}return!0}ji(){if(this.Ki)return null;let e=new ge(ve.comparator);const t=[];for(const n of this.$i)if(!n.field.isKeyField())if(n.op==="array-contains"||n.op==="array-contains-any")t.push(new aa(n.field,2));else{if(e.has(n.field))continue;e=e.add(n.field),t.push(new aa(n.field,0))}for(const n of this.qi)n.field.isKeyField()||e.has(n.field)||(e=e.add(n.field),t.push(new aa(n.field,n.dir==="asc"?0:1)));return new va(va.UNKNOWN_ID,this.collectionId,t,ji.empty())}Qi(e){for(const t of this.$i)if(this.Gi(t,e))return!0;return!1}Gi(e,t){if(e===void 0||!e.field.isEqual(t.fieldPath))return!1;const n=e.op==="array-contains"||e.op==="array-contains-any";return t.kind===2===n}zi(e,t){return!!e.field.isEqual(t.fieldPath)&&(t.kind===0&&e.dir==="asc"||t.kind===1&&e.dir==="desc")}}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function kp(r){var t,n;if(U(r instanceof ce||r instanceof _e,20012),r instanceof ce){if(r instanceof k6){const s=((n=(t=r.value.arrayValue)==null?void 0:t.values)==null?void 0:n.map(i=>ce.create(r.field,"==",i)))||[];return _e.create(s,"or")}return r}const e=r.filters.map(s=>kp(s));return _e.create(e,r.op)}function x8(r){if(r.getFilters().length===0)return[];const e=Jc(kp(r));return U(Lp(e),7391),Yc(e)||Xc(e)?[e]:e.getFilters()}function Yc(r){return r instanceof ce}function Xc(r){return r instanceof _e&&b1(r)}function Lp(r){return Yc(r)||Xc(r)||function(t){if(t instanceof _e&&Uc(t)){for(const n of t.getFilters())if(!Yc(n)&&!Xc(n))return!1;return!0}return!1}(r)}function Jc(r){if(U(r instanceof ce||r instanceof _e,34018),r instanceof ce)return r;if(r.filters.length===1)return Jc(r.filters[0]);const e=r.filters.map(n=>Jc(n));let t=_e.create(e,r.op);return t=Ma(t),Lp(t)?t:(U(t instanceof _e,64498),U(vs(t),40251),U(t.filters.length>1,57927),t.filters.reduce((n,s)=>rl(n,s)))}function rl(r,e){let t;return U(r instanceof ce||r instanceof _e,38388),U(e instanceof ce||e instanceof _e,25473),t=r instanceof ce?e instanceof ce?function(s,i){return _e.create([s,i],"and")}(r,e):Tf(r,e):e instanceof ce?Tf(e,r):function(s,i){if(U(s.filters.length>0&&i.filters.length>0,48005),vs(s)&&vs(i))return x6(s,i.getFilters());const o=Uc(s)?s:i,u=Uc(s)?i:s,c=o.filters.map(l=>rl(l,u));return _e.create(c,"or")}(r,e),Ma(t)}function Tf(r,e){if(vs(e))return x6(e,r.getFilters());{const t=e.filters.map(n=>rl(r,n));return _e.create(t,"or")}}function Ma(r){if(U(r instanceof ce||r instanceof _e,11850),r instanceof ce)return r;const e=r.getFilters();if(e.length===1)return Ma(e[0]);if(N6(r))return r;const t=e.map(s=>Ma(s)),n=[];return t.forEach(s=>{s instanceof ce?n.push(s):s instanceof _e&&(s.op===r.op?n.push(...s.filters):n.push(s))}),n.length===1?n[0]:_e.create(n,r.op)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class D8{constructor(){this.Hi=new sl}addToCollectionParentIndex(e,t){return this.Hi.add(t),P.resolve()}getCollectionParents(e,t){return P.resolve(this.Hi.getEntries(t))}addFieldIndex(e,t){return P.resolve()}deleteFieldIndex(e,t){return P.resolve()}deleteAllFieldIndexes(e){return P.resolve()}createTargetIndexes(e,t){return P.resolve()}getDocumentsMatchingTarget(e,t){return P.resolve(null)}getIndexType(e,t){return P.resolve(0)}getFieldIndexes(e,t){return P.resolve([])}getNextCollectionGroupToUpdate(e){return P.resolve(null)}getMinOffset(e,t){return P.resolve(Tt.min())}getMinOffsetFromCollectionGroup(e,t){return P.resolve(Tt.min())}updateCollectionGroup(e,t,n){return P.resolve()}updateIndexEntries(e,t){return P.resolve()}}class sl{constructor(){this.index={}}add(e){const t=e.lastSegment(),n=e.popLast(),s=this.index[t]||new ge(ae.comparator),i=!s.has(n);return this.index[t]=s.add(n),i}has(e){const t=e.lastSegment(),n=e.popLast(),s=this.index[t];return s&&s.has(n)}getEntries(e){return(this.index[e]||new ge(ae.comparator)).toArray()}}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Af="IndexedDbIndexManager",ta=new Uint8Array(0);class O8{constructor(e,t){this.databaseId=t,this.Ji=new sl,this.Yi=new En(n=>Va(n),(n,s)=>C1(n,s)),this.uid=e.uid||""}addToCollectionParentIndex(e,t){if(!this.Ji.has(t)){const n=t.lastSegment(),s=t.popLast();e.addOnCommittedListener(()=>{this.Ji.add(t)});const i={collectionId:n,parent:rt(s)};return vf(e).put(i)}return P.resolve()}getCollectionParents(e,t){const n=[],s=IDBKeyRange.bound([t,""],[Q2(t),""],!1,!0);return vf(e).H(s).next(i=>{for(const o of i){if(o.collectionId!==t)break;n.push(Gt(o.parent))}return n})}addFieldIndex(e,t){const n=yi(e),s=function(u){return{indexId:u.indexId,collectionGroup:u.collectionGroup,fields:u.fields.map(c=>[c.fieldPath.canonicalString(),c.kind])}}(t);delete s.indexId;const i=n.add(s);if(t.indexState){const o=Zr(e);return i.next(u=>{o.put(gf(u,this.uid,t.indexState.sequenceNumber,t.indexState.offset))})}return i.next()}deleteFieldIndex(e,t){const n=yi(e),s=Zr(e),i=Jr(e);return n.delete(t.indexId).next(()=>s.delete(IDBKeyRange.bound([t.indexId],[t.indexId+1],!1,!0))).next(()=>i.delete(IDBKeyRange.bound([t.indexId],[t.indexId+1],!1,!0)))}deleteAllFieldIndexes(e){const t=yi(e),n=Jr(e),s=Zr(e);return t.Z().next(()=>n.Z()).next(()=>s.Z())}createTargetIndexes(e,t){return P.forEach(this.Zi(t),n=>this.getIndexType(e,n).next(s=>{if(s===0||s===1){const i=new wf(n).ji();if(i!=null)return this.addFieldIndex(e,i)}}))}getDocumentsMatchingTarget(e,t){const n=Jr(e);let s=!0;const i=new Map;return P.forEach(this.Zi(t),o=>this.Xi(e,o).next(u=>{s&&(s=!!u),i.set(o,u)})).next(()=>{if(s){let o=se();const u=[];return P.forEach(i,(c,l)=>{L(Af,`Using index ${function(ne){return`id=${ne.indexId}|cg=${ne.collectionGroup}|f=${ne.fields.map(Te=>`${Te.fieldPath}:${Te.kind}`).join(",")}`}(c)} to execute ${Va(t)}`);const d=function(ne,Te){const de=xc(Te);if(de===void 0)return null;for(const fe of xa(ne,de.fieldPath))switch(fe.op){case"array-contains-any":return fe.value.arrayValue.values||[];case"array-contains":return[fe.value]}return null}(l,c),g=function(ne,Te){const de=new Map;for(const fe of fr(Te))for(const A of xa(ne,fe.fieldPath))switch(A.op){case"==":case"in":de.set(fe.fieldPath.canonicalString(),A.value);break;case"not-in":case"!=":return de.set(fe.fieldPath.canonicalString(),A.value),Array.from(de.values())}return null}(l,c),y=function(ne,Te){const de=[];let fe=!0;for(const A of fr(Te)){const E=A.kind===0?Kd(ne,A.fieldPath,ne.startAt):Wd(ne,A.fieldPath,ne.startAt);de.push(E.value),fe&&(fe=E.inclusive)}return new As(de,fe)}(l,c),R=function(ne,Te){const de=[];let fe=!0;for(const A of fr(Te)){const E=A.kind===0?Wd(ne,A.fieldPath,ne.endAt):Kd(ne,A.fieldPath,ne.endAt);de.push(E.value),fe&&(fe=E.inclusive)}return new As(de,fe)}(l,c),C=this.es(c,l,y),M=this.es(c,l,R),q=this.ts(c,l,g),Q=this.ns(c.indexId,d,C,y.inclusive,M,R.inclusive,q);return P.forEach(Q,te=>n.Y(te,t.limit).next(ne=>{ne.forEach(Te=>{const de=$.fromSegments(Te.documentKey);o.has(de)||(o=o.add(de),u.push(de))})}))}).next(()=>u)}return P.resolve(null)})}Zi(e){let t=this.Yi.get(e);return t||(e.filters.length===0?t=[e]:t=x8(_e.create(e.filters,"and")).map(n=>qc(e.path,e.collectionGroup,e.orderBy,n.getFilters(),e.limit,e.startAt,e.endAt)),this.Yi.set(e,t),t)}ns(e,t,n,s,i,o,u){const c=(t!=null?t.length:1)*Math.max(n.length,i.length),l=c/(t!=null?t.length:1),d=[];for(let g=0;g<c;++g){const y=t?this.rs(t[g/l]):ta,R=this.ss(e,y,n[g%l],s),C=this._s(e,y,i[g%l],o),M=u.map(q=>this.ss(e,y,q,!0));d.push(...this.createRange(R,C,M))}return d}ss(e,t,n,s){const i=new Er(e,$.empty(),t,n);return s?i:i.Li()}_s(e,t,n,s){const i=new Er(e,$.empty(),t,n);return s?i.Li():i}Xi(e,t){const n=new wf(t),s=t.collectionGroup!=null?t.collectionGroup:t.path.lastSegment();return this.getFieldIndexes(e,s).next(i=>{let o=null;for(const u of i)n.Wi(u)&&(!o||u.fields.length>o.fields.length)&&(o=u);return o})}getIndexType(e,t){let n=2;const s=this.Zi(t);return P.forEach(s,i=>this.Xi(e,i).next(o=>{o?n!==0&&o.fields.length<function(c){let l=new ge(ve.comparator),d=!1;for(const g of c.filters)for(const y of g.getFlattenedFilters())y.field.isKeyField()||(y.op==="array-contains"||y.op==="array-contains-any"?d=!0:l=l.add(y.field));for(const g of c.orderBy)g.field.isKeyField()||(l=l.add(g.field));return l.size+(d?1:0)}(i)&&(n=1):n=0})).next(()=>function(o){return o.limit!==null}(t)&&s.length>1&&n===2?1:n)}us(e,t){const n=new _i;for(const s of fr(e)){const i=t.data.field(s.fieldPath);if(i==null)return null;const o=n.Ci(s.kind);yr.Ei.Yr(i,o)}return n.Di()}rs(e){const t=new _i;return yr.Ei.Yr(e,t.Ci(0)),t.Di()}cs(e,t){const n=new _i;return yr.Ei.Yr(Qi(this.databaseId,t),n.Ci(function(i){const o=fr(i);return o.length===0?0:o[o.length-1].kind}(e))),n.Di()}ts(e,t,n){if(n===null)return[];let s=[];s.push(new _i);let i=0;for(const o of fr(e)){const u=n[i++];for(const c of s)if(this.ls(t,o.fieldPath)&&Wn(u))s=this.Es(s,o,u);else{const l=c.Ci(o.kind);yr.Ei.Yr(u,l)}}return this.hs(s)}es(e,t,n){return this.ts(e,t,n.position)}hs(e){const t=[];for(let n=0;n<e.length;++n)t[n]=e[n].Di();return t}Es(e,t,n){const s=[...e],i=[];for(const o of n.arrayValue.values||[])for(const u of s){const c=new _i;c.seed(u.Di()),yr.Ei.Yr(o,c.Ci(t.kind)),i.push(c)}return i}ls(e,t){return!!e.filters.find(n=>n instanceof ce&&n.field.isEqual(t)&&(n.op==="in"||n.op==="not-in"))}getFieldIndexes(e,t){const n=yi(e),s=Zr(e);return(t?n.H(Oc,IDBKeyRange.bound(t,t)):n.H()).next(i=>{const o=[];return P.forEach(i,u=>s.get([u.indexId,this.uid]).next(c=>{o.push(function(d,g){const y=g?new ji(g.sequenceNumber,new Tt(Lr(g.readTime),new $(Gt(g.documentKey)),g.largestBatchId)):ji.empty(),R=d.fields.map(([C,M])=>new aa(ve.fromServerFormat(C),M));return new va(d.indexId,d.collectionGroup,R,y)}(u,c))})).next(()=>o)})}getNextCollectionGroupToUpdate(e){return this.getFieldIndexes(e).next(t=>t.length===0?null:(t.sort((n,s)=>{const i=n.indexState.sequenceNumber-s.indexState.sequenceNumber;return i!==0?i:Z(n.collectionGroup,s.collectionGroup)}),t[0].collectionGroup))}updateCollectionGroup(e,t,n){const s=yi(e),i=Zr(e);return this.Ts(e).next(o=>s.H(Oc,IDBKeyRange.bound(t,t)).next(u=>P.forEach(u,c=>i.put(gf(c.indexId,this.uid,o,n)))))}updateIndexEntries(e,t){const n=new Map;return P.forEach(t,(s,i)=>{const o=n.get(s.collectionGroup);return(o?P.resolve(o):this.getFieldIndexes(e,s.collectionGroup)).next(u=>(n.set(s.collectionGroup,u),P.forEach(u,c=>this.Ps(e,s,c).next(l=>{const d=this.Rs(i,c);return l.isEqual(d)?P.resolve():this.Is(e,i,c,l,d)}))))})}As(e,t,n,s){return Jr(e).put(s.Bi(this.uid,this.cs(n,t.key),t.key))}Vs(e,t,n,s){return Jr(e).delete(s.Ui(this.uid,this.cs(n,t.key),t.key))}Ps(e,t,n){const s=Jr(e);let i=new ge(Sn);return s.ee({index:a6,range:IDBKeyRange.only([n.indexId,this.uid,ga(this.cs(n,t))])},(o,u)=>{i=i.add(new Er(n.indexId,t,If(u.arrayValue),If(u.directionalValue)))}).next(()=>i)}Rs(e,t){let n=new ge(Sn);const s=this.us(t,e);if(s==null)return n;const i=xc(t);if(i!=null){const o=e.data.field(i.fieldPath);if(Wn(o))for(const u of o.arrayValue.values||[])n=n.add(new Er(t.indexId,e.key,this.rs(u),s))}else n=n.add(new Er(t.indexId,e.key,ta,s));return n}Is(e,t,n,s,i){L(Af,"Updating index entries for document '%s'",t.key);const o=[];return function(c,l,d,g,y){const R=c.getIterator(),C=l.getIterator();let M=Qr(R),q=Qr(C);for(;M||q;){let Q=!1,te=!1;if(M&&q){const ne=d(M,q);ne<0?te=!0:ne>0&&(Q=!0)}else M!=null?te=!0:Q=!0;Q?(g(q),q=Qr(C)):te?(y(M),M=Qr(R)):(M=Qr(R),q=Qr(C))}}(s,i,Sn,u=>{o.push(this.As(e,t,n,u))},u=>{o.push(this.Vs(e,t,n,u))}),P.waitFor(o)}Ts(e){let t=1;return Zr(e).ee({index:o6,reverse:!0,range:IDBKeyRange.upperBound([this.uid,Number.MAX_SAFE_INTEGER])},(n,s,i)=>{i.done(),t=s.sequenceNumber+1}).next(()=>t)}createRange(e,t,n){n=n.sort((o,u)=>Sn(o,u)).filter((o,u,c)=>!u||Sn(o,c[u-1])!==0);const s=[];s.push(e);for(const o of n){const u=Sn(o,e),c=Sn(o,t);if(u===0)s[0]=e.Li();else if(u>0&&c<0)s.push(o),s.push(o.Li());else if(c>0)break}s.push(t);const i=[];for(let o=0;o<s.length;o+=2){if(this.ds(s[o],s[o+1]))return[];const u=s[o].Ui(this.uid,ta,$.empty()),c=s[o+1].Ui(this.uid,ta,$.empty());i.push(IDBKeyRange.bound(u,c))}return i}ds(e,t){return Sn(e,t)>0}getMinOffsetFromCollectionGroup(e,t){return this.getFieldIndexes(e,t).next(Rf)}getMinOffset(e,t){return P.mapArray(this.Zi(t),n=>this.Xi(e,n).next(s=>s||j(44426))).next(Rf)}}function vf(r){return $e(r,Ki)}function Jr(r){return $e(r,Ni)}function yi(r){return $e(r,E1)}function Zr(r){return $e(r,Ci)}function Rf(r){U(r.length!==0,28825);let e=r[0].indexState.offset,t=e.largestBatchId;for(let n=1;n<r.length;n++){const s=r[n].indexState.offset;m1(s,e)<0&&(e=s),t<s.largestBatchId&&(t=s.largestBatchId)}return new Tt(e.readTime,e.documentKey,t)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Mp(r,e,t){const n=r.store(bt),s=r.store(ps),i=[],o=IDBKeyRange.only(t.batchId);let u=0;const c=n.ee({range:o},(d,g,y)=>(u++,y.delete()));i.push(c.next(()=>{U(u===1,47070,{batchId:t.batchId})}));const l=[];for(const d of t.mutations){const g=r6(e,d.key.path,t.batchId);i.push(s.delete(g)),l.push(d.key)}return P.waitFor(i).next(()=>l)}function Fa(r){if(!r)return 0;let e;if(r.document)e=r.document;else if(r.unknownDocument)e=r.unknownDocument;else{if(!r.noDocument)throw j(14731);e=r.noDocument}return JSON.stringify(e).length}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Iu{constructor(e,t,n,s){this.userId=e,this.serializer=t,this.indexManager=n,this.referenceDelegate=s,this.fs={}}static jr(e,t,n,s){U(e.uid!=="",64387);const i=e.isAuthenticated()?e.uid:"";return new Iu(i,t,n,s)}checkEmpty(e){let t=!0;const n=IDBKeyRange.bound([this.userId,Number.NEGATIVE_INFINITY],[this.userId,Number.POSITIVE_INFINITY]);return Pn(e).ee({index:Ir,range:n},(s,i,o)=>{t=!1,o.done()}).next(()=>t)}addMutationBatch(e,t,n,s){const i=ss(e),o=Pn(e);return o.add({}).next(u=>{U(typeof u=="number",49019);const c=new el(u,t,n,s),l=function(R,C,M){const q=M.baseMutations.map(te=>eo(R.zr,te)),Q=M.mutations.map(te=>eo(R.zr,te));return{userId:C,batchId:M.batchId,localWriteTimeMs:M.localWriteTime.toMillis(),baseMutations:q,mutations:Q}}(this.serializer,this.userId,c),d=[];let g=new ge((y,R)=>Z(y.canonicalString(),R.canonicalString()));for(const y of s){const R=r6(this.userId,y.key.path,u);g=g.add(y.key.path.popLast()),d.push(o.put(l)),d.push(i.put(R,f4))}return g.forEach(y=>{d.push(this.indexManager.addToCollectionParentIndex(e,y))}),e.addOnCommittedListener(()=>{this.fs[u]=c.keys()}),P.waitFor(d).next(()=>c)})}lookupMutationBatch(e,t){return Pn(e).get(t).next(n=>n?(U(n.userId===this.userId,48,"Unexpected user for mutation batch",{userId:n.userId,batchId:t}),_r(this.serializer,n)):null)}ps(e,t){return this.fs[t]?P.resolve(this.fs[t]):this.lookupMutationBatch(e,t).next(n=>{if(n){const s=n.keys();return this.fs[t]=s,s}return null})}getNextMutationBatchAfterBatchId(e,t){const n=t+1,s=IDBKeyRange.lowerBound([this.userId,n]);let i=null;return Pn(e).ee({index:Ir,range:s},(o,u,c)=>{u.userId===this.userId&&(U(u.batchId>=n,47524,{gs:n}),i=_r(this.serializer,u)),c.done()}).next(()=>i)}getHighestUnacknowledgedBatchId(e){const t=IDBKeyRange.upperBound([this.userId,Number.POSITIVE_INFINITY]);let n=vr;return Pn(e).ee({index:Ir,range:t,reverse:!0},(s,i,o)=>{n=i.batchId,o.done()}).next(()=>n)}getAllMutationBatches(e){const t=IDBKeyRange.bound([this.userId,vr],[this.userId,Number.POSITIVE_INFINITY]);return Pn(e).H(Ir,t).next(n=>n.map(s=>_r(this.serializer,s)))}getAllMutationBatchesAffectingDocumentKey(e,t){const n=ua(this.userId,t.path),s=IDBKeyRange.lowerBound(n),i=[];return ss(e).ee({range:s},(o,u,c)=>{const[l,d,g]=o,y=Gt(d);if(l===this.userId&&t.path.isEqual(y))return Pn(e).get(g).next(R=>{if(!R)throw j(61480,{ys:o,batchId:g});U(R.userId===this.userId,10503,"Unexpected user for mutation batch",{userId:R.userId,batchId:g}),i.push(_r(this.serializer,R))});c.done()}).next(()=>i)}getAllMutationBatchesAffectingDocumentKeys(e,t){let n=new ge(Z);const s=[];return t.forEach(i=>{const o=ua(this.userId,i.path),u=IDBKeyRange.lowerBound(o),c=ss(e).ee({range:u},(l,d,g)=>{const[y,R,C]=l,M=Gt(R);y===this.userId&&i.path.isEqual(M)?n=n.add(C):g.done()});s.push(c)}),P.waitFor(s).next(()=>this.ws(e,n))}getAllMutationBatchesAffectingQuery(e,t){const n=t.path,s=n.length+1,i=ua(this.userId,n),o=IDBKeyRange.lowerBound(i);let u=new ge(Z);return ss(e).ee({range:o},(c,l,d)=>{const[g,y,R]=c,C=Gt(y);g===this.userId&&n.isPrefixOf(C)?C.length===s&&(u=u.add(R)):d.done()}).next(()=>this.ws(e,u))}ws(e,t){const n=[],s=[];return t.forEach(i=>{s.push(Pn(e).get(i).next(o=>{if(o===null)throw j(35274,{batchId:i});U(o.userId===this.userId,9748,"Unexpected user for mutation batch",{userId:o.userId,batchId:i}),n.push(_r(this.serializer,o))}))}),P.waitFor(s).next(()=>n)}removeMutationBatch(e,t){return Mp(e.le,this.userId,t).next(n=>(e.addOnCommittedListener(()=>{this.bs(t.batchId)}),P.forEach(n,s=>this.referenceDelegate.markPotentiallyOrphaned(e,s))))}bs(e){delete this.fs[e]}performConsistencyCheck(e){return this.checkEmpty(e).next(t=>{if(!t)return P.resolve();const n=IDBKeyRange.lowerBound(function(o){return[o]}(this.userId)),s=[];return ss(e).ee({range:n},(i,o,u)=>{if(i[0]===this.userId){const c=Gt(i[1]);s.push(c)}else u.done()}).next(()=>{U(s.length===0,56720,{vs:s.map(i=>i.canonicalString())})})})}containsKey(e,t){return Fp(e,this.userId,t)}Ss(e){return Up(e).get(this.userId).next(t=>t||{userId:this.userId,lastAcknowledgedBatchId:vr,lastStreamToken:""})}}function Fp(r,e,t){const n=ua(e,t.path),s=n[1],i=IDBKeyRange.lowerBound(n);let o=!1;return ss(r).ee({range:i,X:!0},(u,c,l)=>{const[d,g,y]=u;d===e&&g===s&&(o=!0),l.done()}).next(()=>o)}function Pn(r){return $e(r,bt)}function ss(r){return $e(r,ps)}function Up(r){return $e(r,zi)}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class gn{constructor(e){this.Ds=e}next(){return this.Ds+=2,this.Ds}static xs(){return new gn(0)}static Cs(){return new gn(-1)}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class k8{constructor(e,t){this.referenceDelegate=e,this.serializer=t}allocateTargetId(e){return this.Fs(e).next(t=>{const n=new gn(t.highestTargetId);return t.highestTargetId=n.next(),this.Os(e,t).next(()=>t.highestTargetId)})}getLastRemoteSnapshotVersion(e){return this.Fs(e).next(t=>K.fromTimestamp(new me(t.lastRemoteSnapshotVersion.seconds,t.lastRemoteSnapshotVersion.nanoseconds)))}getHighestSequenceNumber(e){return this.Fs(e).next(t=>t.highestListenSequenceNumber)}setTargetsMetadata(e,t,n){return this.Fs(e).next(s=>(s.highestListenSequenceNumber=t,n&&(s.lastRemoteSnapshotVersion=n.toTimestamp()),t>s.highestListenSequenceNumber&&(s.highestListenSequenceNumber=t),this.Os(e,s)))}addTargetData(e,t){return this.Ms(e,t).next(()=>this.Fs(e).next(n=>(n.targetCount+=1,this.Ns(t,n),this.Os(e,n))))}updateTargetData(e,t){return this.Ms(e,t)}removeTargetData(e,t){return this.removeMatchingKeysForTargetId(e,t.targetId).next(()=>es(e).delete(t.targetId)).next(()=>this.Fs(e)).next(n=>(U(n.targetCount>0,8065),n.targetCount-=1,this.Os(e,n)))}removeTargets(e,t,n){let s=0;const i=[];return es(e).ee((o,u)=>{const c=vi(this.serializer,u);c.sequenceNumber<=t&&n.get(c.targetId)===null&&(s++,i.push(this.removeTargetData(e,c)))}).next(()=>P.waitFor(i)).next(()=>s)}forEachTarget(e,t){return es(e).ee((n,s)=>{const i=vi(this.serializer,s);t(i)})}Fs(e){return Sf(e).get(Pa).next(t=>(U(t!==null,2888),t))}Os(e,t){return Sf(e).put(Pa,t)}Ms(e,t){return es(e).put(Dp(this.serializer,t))}Ns(e,t){let n=!1;return e.targetId>t.highestTargetId&&(t.highestTargetId=e.targetId,n=!0),e.sequenceNumber>t.highestListenSequenceNumber&&(t.highestListenSequenceNumber=e.sequenceNumber,n=!0),n}getTargetCount(e){return this.Fs(e).next(t=>t.targetCount)}getTargetData(e,t){const n=yu(t),s=IDBKeyRange.bound([n,Number.NEGATIVE_INFINITY],[n,Number.POSITIVE_INFINITY]);let i=null;return es(e).ee({range:s,index:i6},(o,u,c)=>{const l=vi(this.serializer,u);Z1(t,l.target)&&(i=l,c.done())}).next(()=>i)}addMatchingKeys(e,t,n){const s=[],i=Dn(e);return t.forEach(o=>{const u=rt(o.path);s.push(i.put({targetId:n,path:u})),s.push(this.referenceDelegate.addReference(e,n,o))}),P.waitFor(s)}removeMatchingKeys(e,t,n){const s=Dn(e);return P.forEach(t,i=>{const o=rt(i.path);return P.waitFor([s.delete([n,o]),this.referenceDelegate.removeReference(e,n,i)])})}removeMatchingKeysForTargetId(e,t){const n=Dn(e),s=IDBKeyRange.bound([t],[t+1],!1,!0);return n.delete(s)}getMatchingKeysForTargetId(e,t){const n=IDBKeyRange.bound([t],[t+1],!1,!0),s=Dn(e);let i=se();return s.ee({range:n,X:!0},(o,u,c)=>{const l=Gt(o[1]),d=new $(l);i=i.add(d)}).next(()=>i)}containsKey(e,t){const n=rt(t.path),s=IDBKeyRange.bound([n],[Q2(n)],!1,!0);let i=0;return Dn(e).ee({index:y1,X:!0,range:s},([o,u],c,l)=>{o!==0&&(i++,l.done())}).next(()=>i>0)}dt(e,t){return es(e).get(t).next(n=>n?vi(this.serializer,n):null)}}function es(r){return $e(r,gs)}function Sf(r){return $e(r,Rr)}function Dn(r){return $e(r,ms)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class L8{constructor(e,t){this.db=e,this.garbageCollector=lp(this,t)}lr(e){const t=this.Ls(e);return this.db.getTargetCache().getTargetCount(e).next(n=>t.next(s=>n+s))}Ls(e){let t=0;return this.Er(e,n=>{t++}).next(()=>t)}forEachTarget(e,t){return this.db.getTargetCache().forEachTarget(e,t)}Er(e,t){return this.Bs(e,(n,s)=>t(s))}addReference(e,t,n){return na(e,n)}removeReference(e,t,n){return na(e,n)}removeTargets(e,t,n){return this.db.getTargetCache().removeTargets(e,t,n)}markPotentiallyOrphaned(e,t){return na(e,t)}Us(e,t){return function(s,i){let o=!1;return Up(s).te(u=>Fp(s,u,i).next(c=>(c&&(o=!0),P.resolve(!c)))).next(()=>o)}(e,t)}removeOrphanedDocuments(e,t){const n=this.db.getRemoteDocumentCache().newChangeBuffer(),s=[];let i=0;return this.Bs(e,(o,u)=>{if(u<=t){const c=this.Us(e,o).next(l=>{if(!l)return i++,n.getEntry(e,o).next(()=>(n.removeEntry(o,K.min()),Dn(e).delete(function(g){return[0,rt(g.path)]}(o))))});s.push(c)}}).next(()=>P.waitFor(s)).next(()=>n.apply(e)).next(()=>i)}removeTarget(e,t){const n=t.withSequenceNumber(e.currentSequenceNumber);return this.db.getTargetCache().updateTargetData(e,n)}updateLimboDocument(e,t){return na(e,t)}Bs(e,t){const n=Dn(e);let s,i=ht.ce;return n.ee({index:y1},([o,u],{path:c,sequenceNumber:l})=>{o===0?(i!==ht.ce&&t(new $(Gt(s)),i),i=l,s=c):i=ht.ce}).next(()=>{i!==ht.ce&&t(new $(Gt(s)),i)})}getCacheSize(e){return this.db.getRemoteDocumentCache().getSize(e)}}function na(r,e){return Dn(r).put(function(n,s){return{targetId:0,path:rt(n.path),sequenceNumber:s}}(e,r.currentSequenceNumber))}// Copyright 2024 Google LLC* @license
function Bp(r,e){var n;let t=e;for(const s of r.stages)t=M8({serializer:r.serializer,serverTimestampBehavior:(n=r.listenOptions)==null?void 0:n.serverTimestampBehavior},s,t);return t}function wu(r,e){return Bp(r,[e]).length>0}function qp(r,e){return xe(r)?wu(r,e):hu(r,e)}function M8(r,e,t){if(e instanceof wo)return function(s,i,o){return o.filter(u=>u.isFoundDocument()&&`/${u.key.getCollectionPath().canonicalString()}`===i.Vr)}(0,e,t);if(e instanceof Ao)return function(s,i,o){return o.filter(u=>{const c=Mi(Y(i.condition).evaluate(s,u));return c!==void 0&&St(c,pt)})}(r,e,t);if(e instanceof To)return function(s,i,o){return o.filter(u=>u.isFoundDocument()&&u.key.getCollectionPath().lastSegment()===i.collectionId)}(0,e,t);if(e instanceof pu)return function(s,i,o){return o.filter(u=>u.isFoundDocument())}(0,0,t);if(e instanceof gu)return function(s,i,o){return o.filter(u=>u.isFoundDocument()&&i.mr.has(u.key.path.toStringWithLeadingSlash()))}(0,e,t);if(e instanceof Xn)return function(s,i,o){return o.slice(0,i.limit)}(0,e,t);if(e instanceof zt)return function(s,i,o){const u=i.orderings.map(c=>({ks:Y(c.expr),direction:c.direction}));return[...o].sort((c,l)=>{for(const{ks:d,direction:g}of u){const y=Mi(d.evaluate(s,c)),R=Mi(d.evaluate(s,l)),C=st(y??Kt,R??Kt);if(C!==0)return g==="ascending"?C:-C}return 0})}(r,e,t);throw new Error(`Unknown stage: ${e._name}`)}function Zc(r){const e=function(n){for(let s=n.stages.length-1;s>=0;s--){const i=n.stages[s];if(i instanceof zt)return i.orderings}throw new Error("Pipeline must contain at least one Sort stage")}(r);return(t,n)=>{for(const s of e){const i=Mi(Y(s.expr).evaluate({serializer:r.serializer},t)),o=Mi(Y(s.expr).evaluate({serializer:r.serializer},n)),u=st(i||Kt,o||Kt);if(u!==0)return s.direction==="ascending"?u:-u}return 0}}function hc(r){for(let e=r.stages.length-1;e>=0;e--){const t=r.stages[e];if(t instanceof Xn)return{limit:t.limit}}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class $p{constructor(){this.changes=new En(e=>e.toString(),(e,t)=>e.isEqual(t)),this.changesApplied=!1}addEntry(e){this.assertNotApplied(),this.changes.set(e.key,e)}removeEntry(e,t){this.assertNotApplied(),this.changes.set(e,Re.newInvalidDocument(e).setReadTime(t))}getEntry(e,t){this.assertNotApplied();const n=this.changes.get(t);return n!==void 0?P.resolve(n):this.getFromCache(e,t)}getEntries(e,t){return this.getAllFromCache(e,t)}apply(e){return this.assertNotApplied(),this.changesApplied=!0,this.applyChanges(e)}assertNotApplied(){}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class F8{constructor(e){this.serializer=e}setIndexManager(e){this.indexManager=e}addEntry(e,t,n){return bn(e).put(n)}removeEntry(e,t,n){return bn(e).delete(function(i,o){const u=i.path.toArray();return[u.slice(0,u.length-2),u[u.length-2],La(o),u[u.length-1]]}(t,n))}updateMetadata(e,t){return this.getMetadata(e).next(n=>(n.byteSize+=t,this.qs(e,n)))}getEntry(e,t){let n=Re.newInvalidDocument(t);return bn(e).ee({index:ca,range:IDBKeyRange.only(Ei(t))},(s,i)=>{n=this.$s(t,i)}).next(()=>n)}Ks(e,t){let n={size:0,document:Re.newInvalidDocument(t)};return bn(e).ee({index:ca,range:IDBKeyRange.only(Ei(t))},(s,i)=>{n={document:this.$s(t,i),size:Fa(i)}}).next(()=>n)}getEntries(e,t){let n=Be();return this.Ws(e,t,(s,i)=>{const o=this.$s(s,i);n=n.insert(s,o)}).next(()=>n)}getAllEntries(e){let t=Be();return bn(e).ee((n,s)=>{const i=this.$s($.fromSegments(s.prefixPath.concat(s.collectionGroup,s.documentId)),s);t=t.insert(i.key,i)}).next(()=>t)}Qs(e,t){let n=Be(),s=new Ie($.comparator);return this.Ws(e,t,(i,o)=>{const u=this.$s(i,o);n=n.insert(i,u),s=s.insert(i,Fa(o))}).next(()=>({documents:n,Gs:s}))}Ws(e,t,n){if(t.isEmpty())return P.resolve();let s=new ge(Cf);t.forEach(c=>s=s.add(c));const i=IDBKeyRange.bound(Ei(s.first()),Ei(s.last())),o=s.getIterator();let u=o.getNext();return bn(e).ee({index:ca,range:i},(c,l,d)=>{const g=$.fromSegments([...l.prefixPath,l.collectionGroup,l.documentId]);for(;u&&Cf(u,g)<0;)n(u,null),u=o.getNext();u&&u.isEqual(g)&&(n(u,l),u=o.hasNext()?o.getNext():null),u?d.j(Ei(u)):d.done()}).next(()=>{for(;u;)n(u,null),u=o.hasNext()?o.getNext():null})}getDocumentsMatchingQuery(e,t,n,s,i){const o=xe(t)?ae.fromString(vo(t)):t.path,u=[o.popLast().toArray(),o.lastSegment(),La(n.readTime),n.documentKey.path.isEmpty()?"":n.documentKey.path.lastSegment()],c=[o.popLast().toArray(),o.lastSegment(),[Number.MAX_SAFE_INTEGER,Number.MAX_SAFE_INTEGER],""];return bn(e).H(IDBKeyRange.bound(u,c,!0)).next(l=>{i==null||i.incrementDocumentReadCount(l.length);let d=Be();for(const g of l){const y=this.$s($.fromSegments(g.prefixPath.concat(g.collectionGroup,g.documentId)),g);y.isFoundDocument()&&(qp(t,y)||s.has(y.key))&&(d=d.insert(y.key,y))}return d})}getAllFromCollectionGroup(e,t,n,s){let i=Be();const o=bf(t,n),u=bf(t,Tt.max());return bn(e).ee({index:s6,range:IDBKeyRange.bound(o,u,!0)},(c,l,d)=>{const g=this.$s($.fromSegments(l.prefixPath.concat(l.collectionGroup,l.documentId)),l);i=i.insert(g.key,g),i.size===s&&d.done()}).next(()=>i)}newChangeBuffer(e){return new U8(this,!!e&&e.trackRemovals)}getSize(e){return this.getMetadata(e).next(t=>t.byteSize)}getMetadata(e){return Pf(e).get(Dc).next(t=>(U(!!t,20021),t))}qs(e,t){return Pf(e).put(Dc,t)}$s(e,t){if(t){const n=v8(this.serializer,t);if(!(n.isNoDocument()&&n.version.isEqual(K.min())))return n}return Re.newInvalidDocument(e)}}function Gp(r){return new F8(r)}class U8 extends $p{constructor(e,t){super(),this.zs=e,this.trackRemovals=t,this.js=new En(n=>n.toString(),(n,s)=>n.isEqual(s))}applyChanges(e){const t=[];let n=0,s=new ge((i,o)=>Z(i.canonicalString(),o.canonicalString()));return this.changes.forEach((i,o)=>{const u=this.js.get(i);if(t.push(this.zs.removeEntry(e,i,u.readTime)),o.isValidDocument()){const c=ff(this.zs.serializer,o);s=s.add(i.path.popLast());const l=Fa(c);n+=l-u.size,t.push(this.zs.addEntry(e,i,c))}else if(n-=u.size,this.trackRemovals){const c=ff(this.zs.serializer,o.convertToNoDocument(K.min()));t.push(this.zs.addEntry(e,i,c))}}),s.forEach(i=>{t.push(this.zs.indexManager.addToCollectionParentIndex(e,i))}),t.push(this.zs.updateMetadata(e,n)),P.waitFor(t)}getFromCache(e,t){return this.zs.Ks(e,t).next(n=>(this.js.set(t,{size:n.size,readTime:n.document.readTime}),n.document))}getAllFromCache(e,t){return this.zs.Qs(e,t).next(({documents:n,Gs:s})=>(s.forEach((i,o)=>{this.js.set(i,{size:o,readTime:n.get(i).readTime})}),n))}}function Pf(r){return $e(r,Hi)}function bn(r){return $e(r,Sa)}function Ei(r){const e=r.path.toArray();return[e.slice(0,e.length-2),e[e.length-2],e[e.length-1]]}function bf(r,e){const t=e.documentKey.path.toArray();return[r,La(e.readTime),t.slice(0,t.length-2),t.length>0?t[t.length-1]:""]}function Cf(r,e){const t=r.path.toArray(),n=e.path.toArray();let s=0;for(let i=0;i<t.length-2&&i<n.length-2;++i)if(s=Z(t[i],n[i]),s)return s;return s=Z(t.length,n.length),s||(s=Z(t[t.length-2],n[n.length-2]),s||Z(t[t.length-1],n[n.length-1]))}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *//**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class B8{constructor(e,t){this.overlayedDocument=e,this.mutatedFields=t}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class jp{constructor(e,t,n,s){this.remoteDocumentCache=e,this.mutationQueue=t,this.documentOverlayCache=n,this.indexManager=s}getDocument(e,t){let n=null;return this.documentOverlayCache.getOverlay(e,t).next(s=>(n=s,this.remoteDocumentCache.getEntry(e,t))).next(s=>(n!==null&&xi(n.mutation,s,dt.empty(),me.now()),s))}getDocuments(e,t){return this.remoteDocumentCache.getEntries(e,t).next(n=>this.getLocalViewOfDocuments(e,n,se()).next(()=>n))}getLocalViewOfDocuments(e,t,n=se()){const s=vt();return this.populateOverlays(e,s,t).next(()=>this.computeViews(e,t,s,n).next(i=>{let o=mr();return i.forEach((u,c)=>{o=o.insert(u,c.overlayedDocument)}),o}))}getOverlayedDocuments(e,t){const n=vt();return this.populateOverlays(e,n,t).next(()=>this.computeViews(e,t,n,se()))}populateOverlays(e,t,n){const s=[];return n.forEach(i=>{t.has(i)||s.push(i)}),this.documentOverlayCache.getOverlays(e,s).next(i=>{i.forEach((o,u)=>{t.set(o,u)})})}computeViews(e,t,n,s){let i=Be();const o=ki(),u=function(){return ki()}();return t.forEach((c,l)=>{const d=n.get(l.key);s.has(l.key)&&(d===void 0||d.mutation instanceof yn)?i=i.insert(l.key,l):d!==void 0?(o.set(l.key,d.mutation.getFieldMask()),xi(d.mutation,l,d.mutation.getFieldMask(),me.now())):o.set(l.key,dt.empty())}),this.recalculateAndSaveOverlays(e,i).next(c=>(c.forEach((l,d)=>o.set(l,d)),t.forEach((l,d)=>u.set(l,new B8(d,o.get(l)??null))),u))}recalculateAndSaveOverlays(e,t){const n=ki();let s=new Ie((o,u)=>o-u),i=se();return this.mutationQueue.getAllMutationBatchesAffectingDocumentKeys(e,t).next(o=>{for(const u of o)u.keys().forEach(c=>{const l=t.get(c);if(l===null)return;let d=n.get(c)||dt.empty();d=u.applyToLocalView(l,d),n.set(c,d);const g=(s.get(u.batchId)||se()).add(c);s=s.insert(u.batchId,g)})}).next(()=>{const o=[],u=s.getReverseIterator();for(;u.hasNext();){const c=u.getNext(),l=c.key,d=c.value,g=$6();d.forEach(y=>{if(!i.has(y)){const R=P6(t.get(y),n.get(y));R!==null&&g.set(y,R),i=i.add(y)}}),o.push(this.documentOverlayCache.saveOverlays(e,l,g))}return P.waitFor(o)}).next(()=>n)}recalculateAndSaveOverlaysForDocumentKeys(e,t){return this.remoteDocumentCache.getEntries(e,t).next(n=>this.recalculateAndSaveOverlays(e,n))}getDocumentsMatchingQuery(e,t,n,s){return xe(t)?this.getDocumentsMatchingPipeline(e,t,n,s):s3(t)?this.getDocumentsMatchingDocumentQuery(e,t.path):M6(t)?this.getDocumentsMatchingCollectionGroupQuery(e,t,n,s):this.getDocumentsMatchingCollectionQuery(e,t,n,s)}getNextDocuments(e,t,n,s){return this.remoteDocumentCache.getAllFromCollectionGroup(e,t,n,s).next(i=>{const o=s-i.size>0?this.documentOverlayCache.getOverlaysForCollectionGroup(e,t,n.largestBatchId,s-i.size):P.resolve(vt());let u=ds,c=i;return o.next(l=>P.forEach(l,(d,g)=>(u<g.largestBatchId&&(u=g.largestBatchId),i.get(d)?P.resolve():this.remoteDocumentCache.getEntry(e,d).next(y=>{c=c.insert(d,y)}))).next(()=>this.populateOverlays(e,l,i)).next(()=>this.computeViews(e,c,l,se())).next(d=>({batchId:u,changes:q6(d)})))})}getDocumentsMatchingDocumentQuery(e,t){return this.getDocument(e,new $(t)).next(n=>{let s=mr();return n.isFoundDocument()&&(s=s.insert(n.key,n)),s})}getDocumentsMatchingCollectionGroupQuery(e,t,n,s){const i=t.collectionGroup;let o=mr();return this.indexManager.getCollectionParents(e,i).next(u=>P.forEach(u,c=>{const l=function(g,y){return new Us(y,null,g.explicitOrderBy.slice(),g.filters.slice(),g.limit,g.limitType,g.startAt,g.endAt)}(t,c.child(i));return this.getDocumentsMatchingCollectionQuery(e,l,n,s).next(d=>{d.forEach((g,y)=>{o=o.insert(g,y)})})}).next(()=>o))}getDocumentsMatchingCollectionQuery(e,t,n,s){let i;return this.documentOverlayCache.getOverlaysForCollection(e,t.path,n.largestBatchId).next(o=>(i=o,this.remoteDocumentCache.getDocumentsMatchingQuery(e,t,n,i,s))).next(o=>this.retrieveMatchingLocalDocuments(i,o,u=>hu(t,u)))}getDocumentsMatchingPipeline(e,t,n,s){if(un(t)==="collection_group"){const i=H1(t);let o=mr();return this.indexManager.getCollectionParents(e,i).next(u=>P.forEach(u,c=>{const l=function(g,y){const R=g.stages.map(C=>C instanceof To?new wo(y.canonicalString(),{}):C);return new nt(g.serializer,R)}(t,c.child(i));return this.getDocumentsMatchingPipeline(e,l,n,s).next(d=>{d.forEach((g,y)=>{o=o.insert(g,y)})})}).next(()=>o))}{let i;return this.getOverlaysForPipeline(e,t,n.largestBatchId).next(o=>{switch(i=o,un(t)){case"collection":return this.remoteDocumentCache.getDocumentsMatchingQuery(e,t,n,i,s);case"documents":let u=se();for(const c of Oa(t))u=u.add($.fromPath(c));return this.remoteDocumentCache.getEntries(e,u);case"database":return this.remoteDocumentCache.getAllEntries(e);default:throw new F("invalid-argument",`Invalid pipeline source to execute offline: ${cn(t)}`)}}).next(o=>this.retrieveMatchingLocalDocuments(i,o,u=>wu(t,u)))}}retrieveMatchingLocalDocuments(e,t,n){e.forEach((i,o)=>{const u=o.getKey();t.get(u)===null&&(t=t.insert(u,Re.newInvalidDocument(u)))});let s=mr();return t.forEach((i,o)=>{const u=e.get(i);u!==void 0&&xi(u.mutation,o,dt.empty(),me.now()),n(o)&&(s=s.insert(i,o))}),s}getOverlaysForPipeline(e,t,n){switch(un(t)){case"collection":return this.documentOverlayCache.getOverlaysForCollection(e,ae.fromString(vo(t)),n);case"collection_group":throw new F("invalid-argument",`Unexpected collection group pipeline: ${cn(t)}`);case"documents":return this.documentOverlayCache.getOverlays(e,Oa(t).map(s=>$.fromPath(s)));case"database":return this.documentOverlayCache.getAllOverlays(e,n);default:throw new F("invalid-argument",`Failed to get overlays for pipeline: ${cn(t)}`)}}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class q8{constructor(e){this.serializer=e,this.Hs=new Map,this.Js=new Map}getBundleMetadata(e,t){return P.resolve(this.Hs.get(t))}saveBundleMetadata(e,t){return this.Hs.set(t.id,function(s){return{id:s.id,version:s.version,createTime:Ke(s.createTime)}}(t)),P.resolve()}getNamedQuery(e,t){return P.resolve(this.Js.get(t))}saveNamedQuery(e,t){return this.Js.set(t.name,function(s){return{name:s.name,query:Op(s.bundledQuery),readTime:Ke(s.readTime)}}(t)),P.resolve()}}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class $8{constructor(){this.overlays=new Ie($.comparator),this.Ys=new Map}getOverlay(e,t){return P.resolve(this.overlays.get(t))}getOverlays(e,t){const n=vt();return P.forEach(t,s=>this.getOverlay(e,s).next(i=>{i!==null&&n.set(s,i)})).next(()=>n)}getAllOverlays(e,t){const n=vt();return this.overlays.forEach((s,i)=>{i.largestBatchId>t&&n.set(s,i)}),P.resolve(n)}saveOverlays(e,t,n){return n.forEach((s,i)=>{this.Hr(e,t,i)}),P.resolve()}removeOverlaysForBatchId(e,t,n){const s=this.Ys.get(n);return s!==void 0&&(s.forEach(i=>this.overlays=this.overlays.remove(i)),this.Ys.delete(n)),P.resolve()}getOverlaysForCollection(e,t,n){const s=vt(),i=t.length+1,o=new $(t.child("")),u=this.overlays.getIteratorFrom(o);for(;u.hasNext();){const c=u.getNext().value,l=c.getKey();if(!t.isPrefixOf(l.path))break;l.path.length===i&&c.largestBatchId>n&&s.set(c.getKey(),c)}return P.resolve(s)}getOverlaysForCollectionGroup(e,t,n,s){let i=new Ie((l,d)=>l-d);const o=this.overlays.getIterator();for(;o.hasNext();){const l=o.getNext().value;if(l.getKey().getCollectionGroup()===t&&l.largestBatchId>n){let d=i.get(l.largestBatchId);d===null&&(d=vt(),i=i.insert(l.largestBatchId,d)),d.set(l.getKey(),l)}}const u=vt(),c=i.getIterator();for(;c.hasNext()&&(c.getNext().value.forEach((l,d)=>u.set(l,d)),!(u.size()>=s)););return P.resolve(u)}Hr(e,t,n){const s=this.overlays.get(n.key);if(s!==null){const o=this.Ys.get(s.largestBatchId).delete(n.key);this.Ys.set(s.largestBatchId,o)}this.overlays=this.overlays.insert(n.key,new nl(t,n));let i=this.Ys.get(t);i===void 0&&(i=se(),this.Ys.set(t,i)),this.Ys.set(t,i.add(n.key))}}/**
 * @license
 * Copyright 2024 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class G8{constructor(){this.sessionToken=Ce.EMPTY_BYTE_STRING}getSessionToken(e){return P.resolve(this.sessionToken)}setSessionToken(e,t){return this.sessionToken=t,P.resolve()}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class il{constructor(){this.Zs=new ge(je.Xs),this.e_=new ge(je.t_)}isEmpty(){return this.Zs.isEmpty()}addReference(e,t){const n=new je(e,t);this.Zs=this.Zs.add(n),this.e_=this.e_.add(n)}n_(e,t){e.forEach(n=>this.addReference(n,t))}removeReference(e,t){this.r_(new je(e,t))}i_(e,t){e.forEach(n=>this.removeReference(n,t))}s_(e){const t=new $(new ae([])),n=new je(t,e),s=new je(t,e+1),i=[];return this.e_.forEachInRange([n,s],o=>{this.r_(o),i.push(o.key)}),i}__(){this.Zs.forEach(e=>this.r_(e))}r_(e){this.Zs=this.Zs.delete(e),this.e_=this.e_.delete(e)}o_(e){const t=new $(new ae([])),n=new je(t,e),s=new je(t,e+1);let i=se();return this.e_.forEachInRange([n,s],o=>{i=i.add(o.key)}),i}containsKey(e){const t=new je(e,0),n=this.Zs.firstAfterOrEqual(t);return n!==null&&e.isEqual(n.key)}}class je{constructor(e,t){this.key=e,this.a_=t}static Xs(e,t){return $.comparator(e.key,t.key)||Z(e.a_,t.a_)}static t_(e,t){return Z(e.a_,t.a_)||$.comparator(e.key,t.key)}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class j8{constructor(e,t){this.indexManager=e,this.referenceDelegate=t,this.mutationQueue=[],this.gs=1,this.u_=new ge(je.Xs)}checkEmpty(e){return P.resolve(this.mutationQueue.length===0)}addMutationBatch(e,t,n,s){const i=this.gs;this.gs++,this.mutationQueue.length>0&&this.mutationQueue[this.mutationQueue.length-1];const o=new el(i,t,n,s);this.mutationQueue.push(o);for(const u of s)this.u_=this.u_.add(new je(u.key,i)),this.indexManager.addToCollectionParentIndex(e,u.key.path.popLast());return P.resolve(o)}lookupMutationBatch(e,t){return P.resolve(this.c_(t))}getNextMutationBatchAfterBatchId(e,t){const n=t+1,s=this.l_(n),i=s<0?0:s;return P.resolve(this.mutationQueue.length>i?this.mutationQueue[i]:null)}getHighestUnacknowledgedBatchId(){return P.resolve(this.mutationQueue.length===0?vr:this.gs-1)}getAllMutationBatches(e){return P.resolve(this.mutationQueue.slice())}getAllMutationBatchesAffectingDocumentKey(e,t){const n=new je(t,0),s=new je(t,Number.POSITIVE_INFINITY),i=[];return this.u_.forEachInRange([n,s],o=>{const u=this.c_(o.a_);i.push(u)}),P.resolve(i)}getAllMutationBatchesAffectingDocumentKeys(e,t){let n=new ge(Z);return t.forEach(s=>{const i=new je(s,0),o=new je(s,Number.POSITIVE_INFINITY);this.u_.forEachInRange([i,o],u=>{n=n.add(u.a_)})}),P.resolve(this.E_(n))}getAllMutationBatchesAffectingQuery(e,t){const n=t.path,s=n.length+1;let i=n;$.isDocumentKey(i)||(i=i.child(""));const o=new je(new $(i),0);let u=new ge(Z);return this.u_.forEachWhile(c=>{const l=c.key.path;return!!n.isPrefixOf(l)&&(l.length===s&&(u=u.add(c.a_)),!0)},o),P.resolve(this.E_(u))}E_(e){const t=[];return e.forEach(n=>{const s=this.c_(n);s!==null&&t.push(s)}),t}removeMutationBatch(e,t){U(this.h_(t.batchId,"removed")===0,55003),this.mutationQueue.shift();let n=this.u_;return P.forEach(t.mutations,s=>{const i=new je(s.key,t.batchId);return n=n.delete(i),this.referenceDelegate.markPotentiallyOrphaned(e,s.key)}).next(()=>{this.u_=n})}bs(e){}containsKey(e,t){const n=new je(t,0),s=this.u_.firstAfterOrEqual(n);return P.resolve(t.isEqual(s&&s.key))}performConsistencyCheck(e){return this.mutationQueue.length,P.resolve()}h_(e,t){return this.l_(e)}l_(e){return this.mutationQueue.length===0?0:e-this.mutationQueue[0].batchId}c_(e){const t=this.l_(e);return t<0||t>=this.mutationQueue.length?null:this.mutationQueue[t]}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class z8{constructor(e){this.T_=e,this.docs=function(){return new Ie($.comparator)}(),this.size=0}setIndexManager(e){this.indexManager=e}addEntry(e,t){const n=t.key,s=this.docs.get(n),i=s?s.size:0,o=this.T_(t);return this.docs=this.docs.insert(n,{document:t.mutableCopy(),size:o}),this.size+=o-i,this.indexManager.addToCollectionParentIndex(e,n.path.popLast())}removeEntry(e){const t=this.docs.get(e);t&&(this.docs=this.docs.remove(e),this.size-=t.size)}getEntry(e,t){const n=this.docs.get(t);return P.resolve(n?n.document.mutableCopy():Re.newInvalidDocument(t))}getEntries(e,t){let n=Be();return t.forEach(s=>{const i=this.docs.get(s);n=n.insert(s,i?i.document.mutableCopy():Re.newInvalidDocument(s))}),P.resolve(n)}getAllEntries(e){let t=Be();return this.docs.forEach((n,s)=>{t=t.insert(n,s.document)}),P.resolve(t)}getDocumentsMatchingQuery(e,t,n,s){let i,o;xe(t)?(i=ae.fromString(vo(t)),o=d=>wu(t,d)):(i=t.path,o=d=>hu(t,d));let u=Be();const c=new $(i.child("__id-9223372036854775808__")),l=this.docs.getIteratorFrom(c);for(;l.hasNext();){const{key:d,value:{document:g}}=l.getNext();if(!i.isPrefixOf(d.path))break;d.path.length>i.length+1||m1(J2(g),n)<=0||(s.has(g.key)||o(g))&&(u=u.insert(g.key,g.mutableCopy()))}return P.resolve(u)}getAllFromCollectionGroup(e,t,n,s){j(9500)}P_(e,t){return P.forEach(this.docs,n=>t(n))}newChangeBuffer(e){return new H8(this)}getSize(e){return P.resolve(this.size)}}class H8 extends $p{constructor(e){super(),this.zs=e}applyChanges(e){const t=[];return this.changes.forEach((n,s)=>{s.isValidDocument()?t.push(this.zs.addEntry(e,s)):this.zs.removeEntry(n)}),P.waitFor(t)}getFromCache(e,t){return this.zs.getEntry(e,t)}getAllFromCache(e,t){return this.zs.getEntries(e,t)}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class K8{constructor(e){this.persistence=e,this.R_=new En(t=>yu(t),Z1),this.lastRemoteSnapshotVersion=K.min(),this.highestTargetId=0,this.I_=0,this.A_=new il,this.targetCount=0,this.V_=gn.xs()}forEachTarget(e,t){return this.R_.forEach((n,s)=>t(s)),P.resolve()}getLastRemoteSnapshotVersion(e){return P.resolve(this.lastRemoteSnapshotVersion)}getHighestSequenceNumber(e){return P.resolve(this.I_)}allocateTargetId(e){return this.highestTargetId=this.V_.next(),P.resolve(this.highestTargetId)}setTargetsMetadata(e,t,n){return n&&(this.lastRemoteSnapshotVersion=n),t>this.I_&&(this.I_=t),P.resolve()}Ms(e){this.R_.set(e.target,e);const t=e.targetId;t>this.highestTargetId&&(this.V_=new gn(t),this.highestTargetId=t),e.sequenceNumber>this.I_&&(this.I_=e.sequenceNumber)}addTargetData(e,t){return this.Ms(t),this.targetCount+=1,P.resolve()}updateTargetData(e,t){return this.Ms(t),P.resolve()}removeTargetData(e,t){return this.R_.delete(t.target),this.A_.s_(t.targetId),this.targetCount-=1,P.resolve()}removeTargets(e,t,n){let s=0;const i=[];return this.R_.forEach((o,u)=>{u.sequenceNumber<=t&&n.get(u.targetId)===null&&(this.R_.delete(o),i.push(this.removeMatchingKeysForTargetId(e,u.targetId)),s++)}),P.waitFor(i).next(()=>s)}getTargetCount(e){return P.resolve(this.targetCount)}getTargetData(e,t){const n=this.R_.get(t)||null;return P.resolve(n)}addMatchingKeys(e,t,n){return this.A_.n_(t,n),P.resolve()}removeMatchingKeys(e,t,n){this.A_.i_(t,n);const s=this.persistence.referenceDelegate,i=[];return s&&t.forEach(o=>{i.push(s.markPotentiallyOrphaned(e,o))}),P.waitFor(i)}removeMatchingKeysForTargetId(e,t){return this.A_.s_(t),P.resolve()}getMatchingKeysForTargetId(e,t){const n=this.A_.o_(t);return P.resolve(n)}containsKey(e,t){return P.resolve(this.A_.containsKey(t))}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class ol{constructor(e,t){this.d_={},this.overlays={},this.f_=new ht(0),this.m_=!1,this.m_=!0,this.p_=new G8,this.referenceDelegate=e(this),this.g_=new K8(this),this.indexManager=new D8,this.remoteDocumentCache=function(s){return new z8(s)}(n=>this.referenceDelegate.y_(n)),this.serializer=new xp(t),this.w_=new q8(this.serializer)}start(){return Promise.resolve()}shutdown(){return this.m_=!1,Promise.resolve()}get started(){return this.m_}setDatabaseDeletedListener(){}setNetworkEnabled(){}getIndexManager(e){return this.indexManager}getDocumentOverlayCache(e){let t=this.overlays[e.toKey()];return t||(t=new $8,this.overlays[e.toKey()]=t),t}getMutationQueue(e,t){let n=this.d_[e.toKey()];return n||(n=new j8(t,this.referenceDelegate),this.d_[e.toKey()]=n),n}getGlobalsCache(){return this.p_}getTargetCache(){return this.g_}getRemoteDocumentCache(){return this.remoteDocumentCache}getBundleCache(){return this.w_}runTransaction(e,t,n){L("MemoryPersistence","Starting transaction:",e);const s=new W8(this.f_.next());return this.referenceDelegate.b_(),n(s).next(i=>this.referenceDelegate.v_(s).next(()=>i)).toPromise().then(i=>(s.raiseOnCommittedEvent(),i))}S_(e,t){return P.or(Object.values(this.d_).map(n=>()=>n.containsKey(e,t)))}}class W8 extends e6{constructor(e){super(),this.currentSequenceNumber=e}}class Tu{constructor(e){this.persistence=e,this.D_=new il,this.x_=null}static C_(e){return new Tu(e)}get F_(){if(this.x_)return this.x_;throw j(60996)}addReference(e,t,n){return this.D_.addReference(n,t),this.F_.delete(n.toString()),P.resolve()}removeReference(e,t,n){return this.D_.removeReference(n,t),this.F_.add(n.toString()),P.resolve()}markPotentiallyOrphaned(e,t){return this.F_.add(t.toString()),P.resolve()}removeTarget(e,t){this.D_.s_(t.targetId).forEach(s=>this.F_.add(s.toString()));const n=this.persistence.getTargetCache();return n.getMatchingKeysForTargetId(e,t.targetId).next(s=>{s.forEach(i=>this.F_.add(i.toString()))}).next(()=>n.removeTargetData(e,t))}b_(){this.x_=new Set}v_(e){const t=this.persistence.getRemoteDocumentCache().newChangeBuffer();return P.forEach(this.F_,n=>{const s=$.fromPath(n);return this.O_(e,s).next(i=>{i||t.removeEntry(s,K.min())})}).next(()=>(this.x_=null,t.apply(e)))}updateLimboDocument(e,t){return this.O_(e,t).next(n=>{n?this.F_.delete(t.toString()):this.F_.add(t.toString())})}y_(e){return 0}O_(e,t){return P.or([()=>P.resolve(this.D_.containsKey(t)),()=>this.persistence.getTargetCache().containsKey(e,t),()=>this.persistence.S_(e,t)])}}class Ua{constructor(e,t){this.persistence=e,this.M_=new En(n=>rt(n.path),(n,s)=>n.isEqual(s)),this.garbageCollector=lp(this,t)}static C_(e,t){return new Ua(e,t)}b_(){}v_(e){return P.resolve()}forEachTarget(e,t){return this.persistence.getTargetCache().forEachTarget(e,t)}lr(e){const t=this.Ls(e);return this.persistence.getTargetCache().getTargetCount(e).next(n=>t.next(s=>n+s))}Ls(e){let t=0;return this.Er(e,n=>{t++}).next(()=>t)}Er(e,t){return P.forEach(this.M_,(n,s)=>this.Us(e,n,s).next(i=>i?P.resolve():t(s)))}removeTargets(e,t,n){return this.persistence.getTargetCache().removeTargets(e,t,n)}removeOrphanedDocuments(e,t){let n=0;const s=this.persistence.getRemoteDocumentCache(),i=s.newChangeBuffer();return s.P_(e,o=>this.Us(e,o,t).next(u=>{u||(n++,i.removeEntry(o,K.min()))})).next(()=>i.apply(e)).next(()=>n)}markPotentiallyOrphaned(e,t){return this.M_.set(t,e.currentSequenceNumber),P.resolve()}removeTarget(e,t){const n=t.withSequenceNumber(e.currentSequenceNumber);return this.persistence.getTargetCache().updateTargetData(e,n)}addReference(e,t,n){return this.M_.set(n,e.currentSequenceNumber),P.resolve()}removeReference(e,t,n){return this.M_.set(n,e.currentSequenceNumber),P.resolve()}updateLimboDocument(e,t){return this.M_.set(t,e.currentSequenceNumber),P.resolve()}y_(e){let t=e.key.toString().length;return e.isFoundDocument()&&(t+=la(e.data.value)),t}Us(e,t,n){return P.or([()=>this.persistence.S_(e,t),()=>this.persistence.getTargetCache().containsKey(e,t),()=>{const s=this.M_.get(t);return P.resolve(s!==void 0&&s>n)}])}getCacheSize(e){return this.persistence.getRemoteDocumentCache().getSize(e)}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Q8{constructor(e){this.serializer=e}U(e,t,n,s){const i=new ru("createOrUpgrade",t);n<1&&s>=1&&(function(c){c.createObjectStore(mo)}(e),function(c){c.createObjectStore(zi,{keyPath:d4}),c.createObjectStore(bt,{keyPath:Od,autoIncrement:!0}).createIndex(Ir,kd,{unique:!0}),c.createObjectStore(ps)}(e),Nf(e),function(c){c.createObjectStore(pr)}(e));let o=P.resolve();return n<3&&s>=3&&(n!==0&&(function(c){c.deleteObjectStore(ms),c.deleteObjectStore(gs),c.deleteObjectStore(Rr)}(e),Nf(e)),o=o.next(()=>function(c){const l=c.store(Rr),d={highestTargetId:0,highestListenSequenceNumber:0,lastRemoteSnapshotVersion:K.min().toTimestamp(),targetCount:0};return l.put(Pa,d)}(i))),n<4&&s>=4&&(n!==0&&(o=o.next(()=>function(c,l){return l.store(bt).H().next(g=>{c.deleteObjectStore(bt),c.createObjectStore(bt,{keyPath:Od,autoIncrement:!0}).createIndex(Ir,kd,{unique:!0});const y=l.store(bt),R=g.map(C=>y.put(C));return P.waitFor(R)})}(e,i))),o=o.next(()=>{(function(c){c.createObjectStore(_s,{keyPath:w4})})(e)})),n<5&&s>=5&&(o=o.next(()=>this.N_(i))),n<6&&s>=6&&(o=o.next(()=>(function(c){c.createObjectStore(Hi)}(e),this.L_(i)))),n<7&&s>=7&&(o=o.next(()=>this.B_(i))),n<8&&s>=8&&(o=o.next(()=>this.U_(e,i))),n<9&&s>=9&&(o=o.next(()=>{(function(c){c.objectStoreNames.contains("remoteDocumentChanges")&&c.deleteObjectStore("remoteDocumentChanges")})(e)})),n<10&&s>=10&&(o=o.next(()=>this.k_(i))),n<11&&s>=11&&(o=o.next(()=>{(function(c){c.createObjectStore(su,{keyPath:T4})})(e),function(c){c.createObjectStore(iu,{keyPath:A4})}(e)})),n<12&&s>=12&&(o=o.next(()=>{(function(c){const l=c.createObjectStore(ou,{keyPath:N4});l.createIndex(kc,V4,{unique:!1}),l.createIndex(u6,x4,{unique:!1})})(e)})),n<13&&s>=13&&(o=o.next(()=>function(c){const l=c.createObjectStore(Sa,{keyPath:p4});l.createIndex(ca,g4),l.createIndex(s6,m4)}(e)).next(()=>this.q_(e,i)).next(()=>e.deleteObjectStore(pr))),n<14&&s>=14&&(o=o.next(()=>this.K_(e,i))),n<15&&s>=15&&(o=o.next(()=>function(c){c.createObjectStore(E1,{keyPath:v4,autoIncrement:!0}).createIndex(Oc,R4,{unique:!1}),c.createObjectStore(Ci,{keyPath:S4}).createIndex(o6,P4,{unique:!1}),c.createObjectStore(Ni,{keyPath:b4}).createIndex(a6,C4,{unique:!1})}(e))),n<16&&s>=16&&(o=o.next(()=>{t.objectStore(Ci).clear()}).next(()=>{t.objectStore(Ni).clear()})),n<17&&s>=17&&(o=o.next(()=>{(function(c){c.createObjectStore(I1,{keyPath:D4})})(e)})),n<18&&s>=18&&D2()&&(o=o.next(()=>{t.objectStore(Ci).clear()}).next(()=>{t.objectStore(Ni).clear()})),o}L_(e){let t=0;return e.store(pr).ee((n,s)=>{t+=Fa(s)}).next(()=>{const n={byteSize:t};return e.store(Hi).put(Dc,n)})}N_(e){const t=e.store(zi),n=e.store(bt);return t.H().next(s=>P.forEach(s,i=>{const o=IDBKeyRange.bound([i.userId,vr],[i.userId,i.lastAcknowledgedBatchId]);return n.H(Ir,o).next(u=>P.forEach(u,c=>{U(c.userId===i.userId,18650,"Cannot process batch from unexpected user",{batchId:c.batchId});const l=_r(this.serializer,c);return Mp(e,i.userId,l).next(()=>{})}))}))}B_(e){const t=e.store(ms),n=e.store(pr);return e.store(Rr).get(Pa).next(s=>{const i=[];return n.ee((o,u)=>{const c=new ae(o),l=function(g){return[0,rt(g)]}(c);i.push(t.get(l).next(d=>d?P.resolve():(g=>t.put({targetId:0,path:rt(g),sequenceNumber:s.highestListenSequenceNumber}))(c)))}).next(()=>P.waitFor(i))})}U_(e,t){e.createObjectStore(Ki,{keyPath:I4});const n=t.store(Ki),s=new sl,i=o=>{if(s.add(o)){const u=o.lastSegment(),c=o.popLast();return n.put({collectionId:u,parent:rt(c)})}};return t.store(pr).ee({X:!0},(o,u)=>{const c=new ae(o);return i(c.popLast())}).next(()=>t.store(ps).ee({X:!0},([o,u,c],l)=>{const d=Gt(u);return i(d.popLast())}))}k_(e){const t=e.store(gs);return t.ee((n,s)=>{const i=vi(this.serializer,s),o=Dp(this.serializer,i);return t.put(o)})}q_(e,t){const n=t.store(pr),s=[];return n.ee((i,o)=>{const u=t.store(Sa),c=function(g){return g.document?new $(ae.fromString(g.document.name).popFirst(5)):g.noDocument?$.fromSegments(g.noDocument.path):g.unknownDocument?$.fromSegments(g.unknownDocument.path):j(36783)}(o).path.toArray(),l={prefixPath:c.slice(0,c.length-2),collectionGroup:c[c.length-2],documentId:c[c.length-1],readTime:o.readTime||[0,0],unknownDocument:o.unknownDocument,noDocument:o.noDocument,document:o.document,hasCommittedMutations:!!o.hasCommittedMutations};s.push(u.put(l))}).next(()=>P.waitFor(s))}K_(e,t){const n=t.store(bt),s=Gp(this.serializer),i=new ol(Tu.C_,this.serializer.zr);return n.H().next(o=>{const u=new Map;return o.forEach(c=>{let l=u.get(c.userId)??se();_r(this.serializer,c).keys().forEach(d=>l=l.add(d)),u.set(c.userId,l)}),P.forEach(u,(c,l)=>{const d=new et(l),g=Eu.jr(this.serializer,d),y=i.getIndexManager(d),R=Iu.jr(d,this.serializer,y,i.referenceDelegate);return new jp(s,R,g,y).recalculateAndSaveOverlaysForDocumentKeys(new Lc(t,ht.ce),c).next()})})}}function Nf(r){r.createObjectStore(ms,{keyPath:y4}).createIndex(y1,E4,{unique:!0}),r.createObjectStore(gs,{keyPath:"targetId"}).createIndex(i6,_4,{unique:!0}),r.createObjectStore(Rr)}const Cn="IndexedDbPersistence",dc=18e5,fc=5e3,pc="Failed to obtain exclusive access to the persistence layer. To allow shared access, multi-tab synchronization has to be enabled in all tabs. If you are using `experimentalForceOwningTab:true`, make sure that only one tab has persistence enabled at any given time.",Y8="main";class al{constructor(e,t,n,s,i,o,u,c,l,d,g=18){if(this.allowTabSynchronization=e,this.persistenceKey=t,this.clientId=n,this.Tn=i,this.window=o,this.document=u,this.W_=l,this.Q_=d,this.G_=g,this.f_=null,this.m_=!1,this.isPrimary=!1,this.networkEnabled=!0,this.z_=null,this.inForeground=!1,this.j_=null,this.H_=null,this.J_=Number.NEGATIVE_INFINITY,this.Y_=y=>Promise.resolve(),!al.C())throw new F(D.UNIMPLEMENTED,"This platform is either missing IndexedDB or is known to have an incomplete implementation. Offline persistence has been disabled.");this.referenceDelegate=new L8(this,s),this.Z_=t+Y8,this.serializer=new xp(c),this.X_=new $n(this.Z_,this.G_,new Q8(this.serializer)),this.p_=new P8,this.g_=new k8(this.referenceDelegate,this.serializer),this.remoteDocumentCache=Gp(this.serializer),this.w_=new S8,this.window&&this.window.localStorage?this.eo=this.window.localStorage:(this.eo=null,d===!1&&ke(Cn,"LocalStorage is unavailable. As a result, persistence may not work reliably. In particular enablePersistence() could fail immediately after refreshing the page."))}start(){return this.no().then(()=>{if(!this.isPrimary&&!this.allowTabSynchronization)throw new F(D.FAILED_PRECONDITION,pc);return this.ro(),this.io(),this.so(),this.runTransaction("getHighestListenSequenceNumber","readonly",e=>this.g_.getHighestSequenceNumber(e))}).then(e=>{this.f_=new ht(e,this.W_)}).then(()=>{this.m_=!0}).catch(e=>(this.X_&&this.X_.close(),Promise.reject(e)))}_o(e){return this.Y_=async t=>{if(this.started)return e(t)},e(this.isPrimary)}setDatabaseDeletedListener(e){this.X_.q(async t=>{t.newVersion===null&&await e()})}setNetworkEnabled(e){this.networkEnabled!==e&&(this.networkEnabled=e,this.Tn.enqueueAndForget(async()=>{this.started&&await this.no()}))}no(){return this.runTransaction("updateClientMetadataAndTryBecomePrimary","readwrite",e=>ra(e).put({clientId:this.clientId,updateTimeMs:Date.now(),networkEnabled:this.networkEnabled,inForeground:this.inForeground}).next(()=>{if(this.isPrimary)return this.oo(e).next(t=>{t||(this.isPrimary=!1,this.Tn.enqueueRetryable(()=>this.Y_(!1)))})}).next(()=>this.ao(e)).next(t=>this.isPrimary&&!t?this.uo(e).next(()=>!1):!!t&&this.co(e).next(()=>!0))).catch(e=>{if(nr(e))return L(Cn,"Failed to extend owner lease: ",e),this.isPrimary;if(!this.allowTabSynchronization)throw e;return L(Cn,"Releasing owner lease after error during lease refresh",e),!1}).then(e=>{this.isPrimary!==e&&this.Tn.enqueueRetryable(()=>this.Y_(e)),this.isPrimary=e})}oo(e){return Ii(e).get(Wr).next(t=>P.resolve(this.lo(t)))}Eo(e){return ra(e).delete(this.clientId)}async ho(){if(this.isPrimary&&!this.To(this.J_,dc)){this.J_=Date.now();const e=await this.runTransaction("maybeGarbageCollectMultiClientState","readwrite-primary",t=>{const n=$e(t,_s);return n.H().next(s=>{const i=this.Po(s,dc),o=s.filter(u=>i.indexOf(u)===-1);return P.forEach(o,u=>n.delete(u.clientId)).next(()=>o)})}).catch(()=>[]);if(this.eo)for(const t of e)this.eo.removeItem(this.Ro(t.clientId))}}so(){this.H_=this.Tn.enqueueAfterDelay("client_metadata_refresh",4e3,()=>this.no().then(()=>this.ho()).then(()=>this.so()))}lo(e){return!!e&&e.ownerId===this.clientId}ao(e){return this.Q_?P.resolve(!0):Ii(e).get(Wr).next(t=>{if(t!==null&&this.To(t.leaseTimestampMs,fc)&&!this.Io(t.ownerId)){if(this.lo(t)&&this.networkEnabled)return!0;if(!this.lo(t)){if(!t.allowTabSynchronization)throw new F(D.FAILED_PRECONDITION,pc);return!1}}return!(!this.networkEnabled||!this.inForeground)||ra(e).H().next(n=>this.Po(n,fc).find(s=>{if(this.clientId!==s.clientId){const i=!this.networkEnabled&&s.networkEnabled,o=!this.inForeground&&s.inForeground,u=this.networkEnabled===s.networkEnabled;if(i||o&&u)return!0}return!1})===void 0)}).next(t=>(this.isPrimary!==t&&L(Cn,`Client ${t?"is":"is not"} eligible for a primary lease.`),t))}async shutdown(){this.m_=!1,this.Ao(),this.H_&&(this.H_.cancel(),this.H_=null),this.Vo(),this.fo(),await this.X_.runTransaction("shutdown","readwrite",[mo,_s],e=>{const t=new Lc(e,ht.ce);return this.uo(t).next(()=>this.Eo(t))}),this.X_.close(),this.mo()}Po(e,t){return e.filter(n=>this.To(n.updateTimeMs,t)&&!this.Io(n.clientId))}po(){return this.runTransaction("getActiveClients","readonly",e=>ra(e).H().next(t=>this.Po(t,dc).map(n=>n.clientId)))}get started(){return this.m_}getGlobalsCache(){return this.p_}getMutationQueue(e,t){return Iu.jr(e,this.serializer,t,this.referenceDelegate)}getTargetCache(){return this.g_}getRemoteDocumentCache(){return this.remoteDocumentCache}getIndexManager(e){return new O8(e,this.serializer.zr.databaseId)}getDocumentOverlayCache(e){return Eu.jr(this.serializer,e)}getBundleCache(){return this.w_}runTransaction(e,t,n){L(Cn,"Starting transaction:",e);const s=t==="readonly"?"readonly":"readwrite",i=function(c){return c===18?L4:c===17?d6:c===16?k4:c===15?w1:c===14?h6:c===13?l6:c===12?O4:c===11?c6:void j(60245)}(this.G_);let o;return this.X_.runTransaction(e,s,i,u=>(o=new Lc(u,this.f_?this.f_.next():ht.ce),t==="readwrite-primary"?this.oo(o).next(c=>!!c||this.ao(o)).next(c=>{if(!c)throw ke(`Failed to obtain primary lease for action '${e}'.`),this.isPrimary=!1,this.Tn.enqueueRetryable(()=>this.Y_(!1)),new F(D.FAILED_PRECONDITION,Z2);return n(o)}).next(c=>this.co(o).next(()=>c)):this.yo(o).next(()=>n(o)))).then(u=>(o.raiseOnCommittedEvent(),u))}yo(e){return Ii(e).get(Wr).next(t=>{if(t!==null&&this.To(t.leaseTimestampMs,fc)&&!this.Io(t.ownerId)&&!this.lo(t)&&!(this.Q_||this.allowTabSynchronization&&t.allowTabSynchronization))throw new F(D.FAILED_PRECONDITION,pc)})}co(e){const t={ownerId:this.clientId,allowTabSynchronization:this.allowTabSynchronization,leaseTimestampMs:Date.now()};return Ii(e).put(Wr,t)}static C(){return $n.C()}uo(e){const t=Ii(e);return t.get(Wr).next(n=>this.lo(n)?(L(Cn,"Releasing primary lease."),t.delete(Wr)):P.resolve())}To(e,t){const n=Date.now();return!(e<n-t)&&(!(e>n)||(ke(`Detected an update time that is in the future: ${e} > ${n}`),!1))}ro(){this.document!==null&&typeof this.document.addEventListener=="function"&&(this.j_=()=>{this.Tn.enqueueAndForget(()=>(this.inForeground=this.document.visibilityState==="visible",this.no()))},this.document.addEventListener("visibilitychange",this.j_),this.inForeground=this.document.visibilityState==="visible")}Vo(){this.j_&&(this.document.removeEventListener("visibilitychange",this.j_),this.j_=null)}io(){var e;typeof((e=this.window)==null?void 0:e.addEventListener)=="function"&&(this.z_=()=>{this.Ao();const t=/(?:Version|Mobile)\/1[456]/;x2()&&(navigator.appVersion.match(t)||navigator.userAgent.match(t))&&this.Tn.enterRestrictedMode(!0),this.Tn.enqueueAndForget(()=>this.shutdown())},this.window.addEventListener("pagehide",this.z_))}fo(){this.z_&&(this.window.removeEventListener("pagehide",this.z_),this.z_=null)}Io(e){var t;try{const n=((t=this.eo)==null?void 0:t.getItem(this.Ro(e)))!==null;return L(Cn,`Client '${e}' ${n?"is":"is not"} zombied in LocalStorage`),n}catch(n){return ke(Cn,"Failed to get zombied client id.",n),!1}}Ao(){if(this.eo)try{this.eo.setItem(this.Ro(this.clientId),String(Date.now()))}catch(e){ke("Failed to set zombie client id.",e)}}mo(){if(this.eo)try{this.eo.removeItem(this.Ro(this.clientId))}catch{}}Ro(e){return`firestore_zombie_${this.persistenceKey}_${e}`}}function Ii(r){return $e(r,mo)}function ra(r){return $e(r,_s)}function zp(r,e){let t=r.projectId;return r.isDefaultDatabase||(t+="."+r.database),"firestore/"+e+"/"+t+"/"}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class ul{constructor(e,t,n,s){this.targetId=e,this.fromCache=t,this.wo=n,this.bo=s}static vo(e,t){let n=se(),s=se();for(const i of t.docChanges)switch(i.type){case 0:n=n.add(i.doc.key);break;case 1:s=s.add(i.doc.key)}return new ul(e,t.fromCache,n,s)}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function X8(r,e){return $.comparator(r.key,e.key)}/**
 * @license
 * Copyright 2023 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class J8{constructor(){this._documentReadCount=0}get documentReadCount(){return this._documentReadCount}incrementDocumentReadCount(e){this._documentReadCount+=e}}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Hp{constructor(){this.So=!1,this.Do=!1,this.xo=100,this.Co=function(){return x2()?8:t6(qe())>0?6:4}()}initialize(e,t){this.Fo=e,this.indexManager=t,this.So=!0}getDocumentsMatchingQuery(e,t,n,s){const i={result:null};return this.Oo(e,t).next(o=>{i.result=o}).next(()=>{if(!i.result)return this.Mo(e,t,s,n).next(o=>{i.result=o})}).next(()=>{if(i.result)return;const o=new J8;return this.No(e,t,o).next(u=>{if(i.result=u,this.Do)return this.Lo(e,t,o,u.size)})}).next(()=>i.result)}Lo(e,t,n,s){return xe(t)?P.resolve():n.documentReadCount<this.xo?(ts()<=ue.DEBUG&&L("QueryEngine","SDK will not create cache indexes for query:",Oi(t),"since it only creates cache indexes for collection contains","more than or equal to",this.xo,"documents"),P.resolve()):(ts()<=ue.DEBUG&&L("QueryEngine","Query:",Oi(t),"scans",n.documentReadCount,"local documents and returns",s,"documents as results."),n.documentReadCount>this.Co*s?(ts()<=ue.DEBUG&&L("QueryEngine","The SDK decides to create cache indexes for query:",Oi(t),"as using cache indexes may help improve performance."),this.indexManager.createTargetIndexes(e,wt(t))):P.resolve())}Oo(e,t){if(xe(t))return P.resolve(null);let n=t;if(Qd(n))return P.resolve(null);let s=wt(n);return this.indexManager.getIndexType(e,s).next(i=>i===0?null:(n.limit!==null&&i===1&&(n=Gc(n,null,"F"),s=wt(n)),this.indexManager.getDocumentsMatchingTarget(e,s).next(o=>{const u=se(...o);return this.Fo.getDocuments(e,u).next(c=>this.indexManager.getMinOffset(e,s).next(l=>{const d=this.Bo(n,c);return this.Uo(n,d,u,l.readTime)?this.Oo(e,Gc(n,null,"F")):this.ko(e,d,n,l)}))})))}Mo(e,t,n,s){return(xe(t)?function(o){for(const u of o.stages){if(u instanceof Xn||u instanceof hf)return!1;if(u instanceof Ao){if(u.condition instanceof Ep&&u.condition._expr.name==="exists"&&u.condition._expr.params[0]instanceof $r&&u.condition._expr.params[0].fieldName===$t)continue;return!1}}return!0}(t):Qd(t))||s.isEqual(K.min())?P.resolve(null):this.Fo.getDocuments(e,n).next(i=>{const o=this.Bo(t,i);return this.Uo(t,o,n,s)?P.resolve(null):(ts()<=ue.DEBUG&&L("QueryEngine","Re-using previous result from %s to execute query: %s",s.toString(),df(t)),this.ko(e,o,t,X2(s,ds)).next(u=>u))})}Bo(e,t){let n,s;return xe(e)?(n=new ge(X8),s=i=>wu(e,i)):(n=new ge(V1(e)),s=i=>hu(e,i)),t.forEach((i,o)=>{s(o)&&(n=n.add(o))}),n}Uo(e,t,n,s){if(xe(e))return function(u){return u.stages.some(c=>c instanceof Xn||c instanceof hf)}(e);if(e.limit===null)return!1;if(n.size!==t.size)return!0;const i=e.limitType==="F"?t.last():t.first();return!!i&&(i.hasPendingWrites||i.version.compareTo(s)>0)}No(e,t,n){return ts()<=ue.DEBUG&&L("QueryEngine","Using full collection scan to execute query:",df(t)),this.Fo.getDocumentsMatchingQuery(e,t,Tt.min(),n)}ko(e,t,n,s){return this.Fo.getDocumentsMatchingQuery(e,n,s).next(i=>(t.forEach(o=>{i=i.insert(o.key,o)}),i))}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const cl="LocalStore",Z8=3e8;class e5{constructor(e,t,n,s){this.persistence=e,this.qo=t,this.serializer=s,this.$o=new Ie(Z),this.Ko=new En(i=>yu(i),Z1),this.Wo=new Map,this.Qo=e.getRemoteDocumentCache(),this.g_=e.getTargetCache(),this.w_=e.getBundleCache(),this.Go(n)}Go(e){this.documentOverlayCache=this.persistence.getDocumentOverlayCache(e),this.indexManager=this.persistence.getIndexManager(e),this.mutationQueue=this.persistence.getMutationQueue(e,this.indexManager),this.localDocuments=new jp(this.Qo,this.mutationQueue,this.documentOverlayCache,this.indexManager),this.Qo.setIndexManager(this.indexManager),this.qo.initialize(this.localDocuments,this.indexManager)}collectGarbage(e){return this.persistence.runTransaction("Collect garbage","readwrite-primary",t=>e.collect(t,this.$o))}}function Kp(r,e,t,n){return new e5(r,e,t,n)}async function Wp(r,e){const t=H(r);return await t.persistence.runTransaction("Handle user change","readonly",n=>{let s;return t.mutationQueue.getAllMutationBatches(n).next(i=>(s=i,t.Go(e),t.mutationQueue.getAllMutationBatches(n))).next(i=>{const o=[],u=[];let c=se();for(const l of s){o.push(l.batchId);for(const d of l.mutations)c=c.add(d.key)}for(const l of i){u.push(l.batchId);for(const d of l.mutations)c=c.add(d.key)}return t.localDocuments.getDocuments(n,c).next(l=>({zo:l,removedBatchIds:o,addedBatchIds:u}))})})}function t5(r,e){const t=H(r);return t.persistence.runTransaction("Acknowledge batch","readwrite-primary",n=>{const s=e.batch.keys(),i=t.Qo.newChangeBuffer({trackRemovals:!0});return function(u,c,l,d){const g=l.batch,y=g.keys();let R=P.resolve();return y.forEach(C=>{R=R.next(()=>d.getEntry(c,C)).next(M=>{const q=l.docVersions.get(C);U(q!==null,48541),M.version.compareTo(q)<0&&(g.applyToRemoteDocument(M,l),M.isValidDocument()&&(M.setReadTime(l.commitVersion),d.addEntry(M)))})}),R.next(()=>u.mutationQueue.removeMutationBatch(c,g))}(t,n,e,i).next(()=>i.apply(n)).next(()=>t.mutationQueue.performConsistencyCheck(n)).next(()=>t.documentOverlayCache.removeOverlaysForBatchId(n,s,e.batch.batchId)).next(()=>t.localDocuments.recalculateAndSaveOverlaysForDocumentKeys(n,function(u){let c=se();for(let l=0;l<u.mutationResults.length;++l)u.mutationResults[l].transformResults.length>0&&(c=c.add(u.batch.mutations[l].key));return c}(e))).next(()=>t.localDocuments.getDocuments(n,s))})}function Qp(r){const e=H(r);return e.persistence.runTransaction("Get last remote snapshot version","readonly",t=>e.g_.getLastRemoteSnapshotVersion(t))}function n5(r,e){const t=H(r),n=e.snapshotVersion;let s=t.$o;return t.persistence.runTransaction("Apply remote event","readwrite-primary",i=>{const o=t.Qo.newChangeBuffer({trackRemovals:!0});s=t.$o;const u=[];e.targetChanges.forEach((d,g)=>{const y=s.get(g);if(!y)return;u.push(t.g_.removeMatchingKeys(i,d.removedDocuments,g).next(()=>t.g_.addMatchingKeys(i,d.addedDocuments,g)));let R=y.withSequenceNumber(i.currentSequenceNumber);e.targetMismatches.get(g)!==null?R=R.withResumeToken(Ce.EMPTY_BYTE_STRING,K.min()).withLastLimboFreeSnapshotVersion(K.min()):d.resumeToken.approximateByteSize()>0&&(R=R.withResumeToken(d.resumeToken,n)),s=s.insert(g,R),function(M,q,Q){return M.resumeToken.approximateByteSize()===0||q.snapshotVersion.toMicroseconds()-M.snapshotVersion.toMicroseconds()>=Z8?!0:Q.addedDocuments.size+Q.modifiedDocuments.size+Q.removedDocuments.size>0}(y,R,d)&&u.push(t.g_.updateTargetData(i,R))});let c=Be(),l=se();if(e.documentUpdates.forEach(d=>{e.resolvedLimboDocuments.has(d)&&u.push(t.persistence.referenceDelegate.updateLimboDocument(i,d))}),u.push(r5(i,o,e.documentUpdates).next(d=>{c=d.jo,l=d.Ho})),!n.isEqual(K.min())){const d=t.g_.getLastRemoteSnapshotVersion(i).next(g=>t.g_.setTargetsMetadata(i,i.currentSequenceNumber,n));u.push(d)}return P.waitFor(u).next(()=>o.apply(i)).next(()=>t.localDocuments.getLocalViewOfDocuments(i,c,l)).next(()=>c)}).then(i=>(t.$o=s,i))}function r5(r,e,t){let n=se(),s=se();return t.forEach(i=>n=n.add(i)),e.getEntries(r,n).next(i=>{let o=Be();return t.forEach((u,c)=>{const l=i.get(u);c.isFoundDocument()!==l.isFoundDocument()&&(s=s.add(u)),c.isNoDocument()&&c.version.isEqual(K.min())?(e.removeEntry(u,c.readTime),o=o.insert(u,c)):!l.isValidDocument()||c.version.compareTo(l.version)>0||c.version.compareTo(l.version)===0&&l.hasPendingWrites?(e.addEntry(c),o=o.insert(u,c)):L(cl,"Ignoring outdated watch update for ",u,". Current version:",l.version," Watch version:",c.version)}),{jo:o,Ho:s}})}function s5(r,e){const t=H(r);return t.persistence.runTransaction("Get next mutation batch","readonly",n=>(e===void 0&&(e=vr),t.mutationQueue.getNextMutationBatchAfterBatchId(n,e)))}function Ba(r,e){const t=H(r);return t.persistence.runTransaction("Allocate target","readwrite",n=>{let s;return t.g_.getTargetData(n,e).next(i=>i?(s=i,P.resolve(s)):t.g_.allocateTargetId(n).next(o=>(s=new Ht(e,o,"TargetPurposeListen",n.currentSequenceNumber),t.g_.addTargetData(n,s).next(()=>s))))}).then(n=>{const s=t.$o.get(n.targetId);return(s===null||n.snapshotVersion.compareTo(s.snapshotVersion)>0)&&(t.$o=t.$o.insert(n.targetId,n),t.Ko.set(e,n.targetId)),n})}async function Ps(r,e,t){const n=H(r),s=n.$o.get(e),i=t?"readwrite":"readwrite-primary";try{t||await n.persistence.runTransaction("Release target",i,o=>n.persistence.referenceDelegate.removeTarget(o,s))}catch(o){if(!nr(o))throw o;L(cl,`Failed to update sequence numbers for target ${e}: ${o}`)}n.$o=n.$o.remove(e),n.Ko.delete(s.target)}function e1(r,e,t){const n=H(r);let s=K.min(),i=se();return n.persistence.runTransaction("Execute query","readwrite",o=>function(c,l,d){const g=H(c),y=g.Ko.get(d);return y!==void 0?P.resolve(g.$o.get(y)):g.g_.getTargetData(l,d)}(n,o,xe(e)?e:wt(e)).next(u=>{if(u)return s=u.lastLimboFreeSnapshotVersion,n.g_.getMatchingKeysForTargetId(o,u.targetId).next(c=>{i=c})}).next(()=>n.qo.getDocumentsMatchingQuery(o,e,t?s:K.min(),t?i:se())).next(u=>(Xp(n,u),{documents:u,Jo:i})))}function Yp(r,e){const t=H(r),n=H(t.g_),s=t.$o.get(e);return s?Promise.resolve(s.target??null):t.persistence.runTransaction("Get target data","readonly",i=>n.dt(i,e).next(o=>(o==null?void 0:o.target)??null))}function t1(r,e){const t=H(r),n=t.Wo.get(e)||K.min();return t.persistence.runTransaction("Get new document changes","readonly",s=>t.Qo.getAllFromCollectionGroup(s,e,X2(n,ds),Number.MAX_SAFE_INTEGER)).then(s=>(Xp(t,s),s))}function Xp(r,e){e.forEach((t,n)=>{const s=n.key.getCollectionGroup(),i=r.Wo.get(s)||K.min();n.readTime.compareTo(i)>0&&r.Wo.set(s,n.readTime)})}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Jp="firestore_clients";function Vf(r,e){return`${Jp}_${r}_${e}`}const Zp="firestore_mutations";function xf(r,e,t){let n=`${Zp}_${r}_${t}`;return e.isAuthenticated()&&(n+=`_${e.uid}`),n}const e0="firestore_targets";function gc(r,e){return`${e0}_${r}_${e}`}/**
 * @license
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const qt="SharedClientState";class qa{constructor(e,t,n,s){this.user=e,this.batchId=t,this.state=n,this.error=s}static ea(e,t,n){const s=JSON.parse(n);let i,o=typeof s=="object"&&["pending","acknowledged","rejected"].indexOf(s.state)!==-1&&(s.error===void 0||typeof s.error=="object");return o&&s.error&&(o=typeof s.error.message=="string"&&typeof s.error.code=="string",o&&(i=new F(s.error.code,s.error.message))),o?new qa(e,t,s.state,i):(ke(qt,`Failed to parse mutation state for ID '${t}': ${n}`),null)}ta(){const e={state:this.state,updateTimeMs:Date.now()};return this.error&&(e.error={code:this.error.code,message:this.error.message}),JSON.stringify(e)}}class Fi{constructor(e,t,n){this.targetId=e,this.state=t,this.error=n}static ea(e,t){const n=JSON.parse(t);let s,i=typeof n=="object"&&["not-current","current","rejected"].indexOf(n.state)!==-1&&(n.error===void 0||typeof n.error=="object");return i&&n.error&&(i=typeof n.error.message=="string"&&typeof n.error.code=="string",i&&(s=new F(n.error.code,n.error.message))),i?new Fi(e,n.state,s):(ke(qt,`Failed to parse target state for ID '${e}': ${t}`),null)}ta(){const e={state:this.state,updateTimeMs:Date.now()};return this.error&&(e.error={code:this.error.code,message:this.error.message}),JSON.stringify(e)}}class $a{constructor(e,t){this.clientId=e,this.activeTargetIds=t}static ea(e,t){const n=JSON.parse(t);let s=typeof n=="object"&&n.activeTargetIds instanceof Array,i=x1();for(let o=0;s&&o<n.activeTargetIds.length;++o)s=n6(n.activeTargetIds[o]),i=i.add(n.activeTargetIds[o]);return s?new $a(e,i):(ke(qt,`Failed to parse client data for instance '${e}': ${t}`),null)}}class ll{constructor(e,t){this.clientId=e,this.onlineState=t}static ea(e){const t=JSON.parse(e);return typeof t=="object"&&["Unknown","Online","Offline"].indexOf(t.onlineState)!==-1&&typeof t.clientId=="string"?new ll(t.clientId,t.onlineState):(ke(qt,`Failed to parse online state: ${e}`),null)}}class n1{constructor(){this.activeTargetIds=x1()}na(e){this.activeTargetIds=this.activeTargetIds.add(e)}ra(e){this.activeTargetIds=this.activeTargetIds.delete(e)}ta(){const e={activeTargetIds:this.activeTargetIds.toArray(),updateTimeMs:Date.now()};return JSON.stringify(e)}}class mc{constructor(e,t,n,s,i){this.window=e,this.Tn=t,this.persistenceKey=n,this.ia=s,this.syncEngine=null,this.onlineStateHandler=null,this.sequenceNumberHandler=null,this.sa=this._a.bind(this),this.oa=new Ie(Z),this.started=!1,this.aa=[];const o=n.replace(/[.*+?^${}()|[\]\\]/g,"\\$&");this.storage=this.window.localStorage,this.currentUser=i,this.ua=Vf(this.persistenceKey,this.ia),this.ca=function(c){return`firestore_sequence_number_${c}`}(this.persistenceKey),this.oa=this.oa.insert(this.ia,new n1),this.la=new RegExp(`^${Jp}_${o}_([^_]*)$`),this.Ea=new RegExp(`^${Zp}_${o}_(\\d+)(?:_(.*))?$`),this.ha=new RegExp(`^${e0}_${o}_(\\d+)$`),this.Ta=function(c){return`firestore_online_state_${c}`}(this.persistenceKey),this.Pa=function(c){return`firestore_bundle_loaded_v2_${c}`}(this.persistenceKey),this.window.addEventListener("storage",this.sa)}static C(e){return!(!e||!e.localStorage)}async start(){const e=await this.syncEngine.po();for(const n of e){if(n===this.ia)continue;const s=this.getItem(Vf(this.persistenceKey,n));if(s){const i=$a.ea(n,s);i&&(this.oa=this.oa.insert(i.clientId,i))}}this.Ra();const t=this.storage.getItem(this.Ta);if(t){const n=this.Ia(t);n&&this.Aa(n)}for(const n of this.aa)this._a(n);this.aa=[],this.window.addEventListener("pagehide",()=>this.shutdown()),this.started=!0}writeSequenceNumber(e){this.setItem(this.ca,JSON.stringify(e))}getAllActiveQueryTargets(){return this.Va(this.oa)}isActiveQueryTarget(e){let t=!1;return this.oa.forEach((n,s)=>{s.activeTargetIds.has(e)&&(t=!0)}),t}addPendingMutation(e){this.da(e,"pending")}updateMutationState(e,t,n){this.da(e,t,n),this.fa(e)}addLocalQueryTarget(e,t=!0){let n="not-current";if(this.isActiveQueryTarget(e)){const s=this.storage.getItem(gc(this.persistenceKey,e));if(s){const i=Fi.ea(e,s);i&&(n=i.state)}}return t&&this.ma.na(e),this.Ra(),n}removeLocalQueryTarget(e){this.ma.ra(e),this.Ra()}isLocalQueryTarget(e){return this.ma.activeTargetIds.has(e)}clearQueryState(e){this.removeItem(gc(this.persistenceKey,e))}updateQueryState(e,t,n){this.pa(e,t,n)}handleUserChange(e,t,n){t.forEach(s=>{this.fa(s)}),this.currentUser=e,n.forEach(s=>{this.addPendingMutation(s)})}setOnlineState(e){this.ga(e)}notifyBundleLoaded(e){this.ya(e)}shutdown(){this.started&&(this.window.removeEventListener("storage",this.sa),this.removeItem(this.ua),this.started=!1)}getItem(e){const t=this.storage.getItem(e);return L(qt,"READ",e,t),t}setItem(e,t){L(qt,"SET",e,t),this.storage.setItem(e,t)}removeItem(e){L(qt,"REMOVE",e),this.storage.removeItem(e)}_a(e){const t=e;if(t.storageArea===this.storage){if(L(qt,"EVENT",t.key,t.newValue),t.key===this.ua)return void ke("Received WebStorage notification for local change. Another client might have garbage-collected our state");this.Tn.enqueueRetryable(async()=>{if(this.started){if(t.key!==null){if(this.la.test(t.key)){if(t.newValue==null){const n=this.wa(t.key);return this.ba(n,null)}{const n=this.va(t.key,t.newValue);if(n)return this.ba(n.clientId,n)}}else if(this.Ea.test(t.key)){if(t.newValue!==null){const n=this.Sa(t.key,t.newValue);if(n)return this.Da(n)}}else if(this.ha.test(t.key)){if(t.newValue!==null){const n=this.xa(t.key,t.newValue);if(n)return this.Ca(n)}}else if(t.key===this.Ta){if(t.newValue!==null){const n=this.Ia(t.newValue);if(n)return this.Aa(n)}}else if(t.key===this.ca){const n=function(i){let o=ht.ce;if(i!=null)try{const u=JSON.parse(i);U(typeof u=="number",30636,{Fa:i}),o=u}catch(u){ke(qt,"Failed to read sequence number from WebStorage",u)}return o}(t.newValue);n!==ht.ce&&this.sequenceNumberHandler(n)}else if(t.key===this.Pa){const n=this.Oa(t.newValue);await Promise.all(n.map(s=>this.syncEngine.Ma(s)))}}}else this.aa.push(t)})}}get ma(){return this.oa.get(this.ia)}Ra(){this.setItem(this.ua,this.ma.ta())}da(e,t,n){const s=new qa(this.currentUser,e,t,n),i=xf(this.persistenceKey,this.currentUser,e);this.setItem(i,s.ta())}fa(e){const t=xf(this.persistenceKey,this.currentUser,e);this.removeItem(t)}ga(e){const t={clientId:this.ia,onlineState:e};this.storage.setItem(this.Ta,JSON.stringify(t))}pa(e,t,n){const s=gc(this.persistenceKey,e),i=new Fi(e,t,n);this.setItem(s,i.ta())}ya(e){const t=JSON.stringify(Array.from(e));this.setItem(this.Pa,t)}wa(e){const t=this.la.exec(e);return t?t[1]:null}va(e,t){const n=this.wa(e);return $a.ea(n,t)}Sa(e,t){const n=this.Ea.exec(e),s=Number(n[1]),i=n[2]!==void 0?n[2]:null;return qa.ea(new et(i),s,t)}xa(e,t){const n=this.ha.exec(e),s=Number(n[1]);return Fi.ea(s,t)}Ia(e){return ll.ea(e)}Oa(e){return JSON.parse(e)}async Da(e){if(e.user.uid===this.currentUser.uid)return this.syncEngine.Na(e.batchId,e.state,e.error);L(qt,`Ignoring mutation for non-active user ${e.user.uid}`)}Ca(e){return this.syncEngine.La(e.targetId,e.state,e.error)}ba(e,t){const n=t?this.oa.insert(e,t):this.oa.remove(e),s=this.Va(this.oa),i=this.Va(n),o=[],u=[];return i.forEach(c=>{s.has(c)||o.push(c)}),s.forEach(c=>{i.has(c)||u.push(c)}),this.syncEngine.Ba(o,u).then(()=>{this.oa=n})}Aa(e){this.oa.get(e.clientId)&&this.onlineStateHandler(e.onlineState)}Va(e){let t=x1();return e.forEach((n,s)=>{t=t.unionWith(s.activeTargetIds)}),t}}class t0{constructor(){this.Ua=new n1,this.ka={},this.onlineStateHandler=null,this.sequenceNumberHandler=null}addPendingMutation(e){}updateMutationState(e,t,n){}addLocalQueryTarget(e,t=!0){return t&&this.Ua.na(e),this.ka[e]||"not-current"}updateQueryState(e,t,n){this.ka[e]=t}removeLocalQueryTarget(e){this.Ua.ra(e)}isLocalQueryTarget(e){return this.Ua.activeTargetIds.has(e)}clearQueryState(e){delete this.ka[e]}getAllActiveQueryTargets(){return this.Ua.activeTargetIds}isActiveQueryTarget(e){return this.Ua.activeTargetIds.has(e)}start(){return this.Ua=new n1,Promise.resolve()}handleUserChange(e,t,n){}setOnlineState(e){}shutdown(){}writeSequenceNumber(e){}notifyBundleLoaded(e){}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function n0(){return typeof window<"u"?window:null}function ma(){return typeof document<"u"?document:null}/**
 * @license
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class i5{constructor(e,t){this.asyncQueue=e,this.onlineStateHandler=t,this.state="Unknown",this.qa=0,this.$a=null,this.Ka=!0}Wa(){this.qa===0&&(this.Qa("Unknown"),this.$a=this.asyncQueue.enqueueAfterDelay("online_state_timeout",1e4,()=>(this.$a=null,this.Ga("Backend didn't respond within 10 seconds."),this.Qa("Offline"),Promise.resolve())))}za(e){this.state==="Online"?this.Qa("Unknown"):(this.qa++,this.qa>=1&&(this.ja(),this.Ga(`Connection failed 1 times. Most recent error: ${e.toString()}`),this.Qa("Offline")))}set(e){this.ja(),this.qa=0,e==="Online"&&(this.Ka=!1),this.Qa(e)}Qa(e){e!==this.state&&(this.state=e,this.onlineStateHandler(e))}Ga(e){const t=`Could not reach Cloud Firestore backend. ${e}
This typically indicates that your device does not have a healthy Internet connection at the moment. The client will operate in offline mode until it is able to successfully connect to the backend.`;this.Ka?(ke(t),this.Ka=!1):L("OnlineStateTracker",t)}ja(){this.$a!==null&&(this.$a.cancel(),this.$a=null)}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Jt="RemoteStore";class o5{constructor(e,t,n,s,i){this.localStore=e,this.datastore=t,this.asyncQueue=n,this.remoteSyncer={},this.Ha=[],this.Ja=new Map,this.Ya=new Map,this.Za=new Map,this.Xa=new gn(1e3),this.eu=new gn(1001),this.tu=new Set,this.nu=[],this.ru=i,this.ru.bt(o=>{n.enqueueAndForget(async()=>{jr(this)&&(L(Jt,"Restarting streams for network reachability change."),await async function(c){const l=H(c);l.tu.add(4),await So(l),l.iu.set("Unknown"),l.tu.delete(4),await Au(l)}(this))})}),this.iu=new i5(n,s)}}async function Au(r){if(jr(r))for(const e of r.nu)await e(!0)}async function So(r){for(const e of r.nu)await e(!1)}function r1(r,e){return r.Ya.get(e)||void 0}function vu(r,e){const t=H(r),n=r1(t,e.targetId);if(n!==void 0&&t.Ja.has(n))return;const s=function(u,c){const l=r1(u,c);l!==void 0&&u.Za.delete(l);const d=function(y,R){return R%2!=0?y.eu.next():y.Xa.next()}(u,c);return u.Ya.set(c,d),u.Za.set(d,c),d}(t,e.targetId);L(Jt,"remoteStoreListen mapping SDK target ID to remote",e.targetId,s);const i=new Ht(e.target,s,e.purpose,e.sequenceNumber,e.snapshotVersion,e.lastLimboFreeSnapshotVersion,e.resumeToken);t.Ja.set(s,i),fl(t)?dl(t):Hs(t).Fn()&&hl(t,i)}function bs(r,e){const t=H(r),n=Hs(t),s=r1(t,e);L(Jt,"remoteStoreUnlisten removing mapping of SDK target ID to remote",e,s),t.Ja.delete(s),t.Ya.delete(e),t.Za.delete(s),n.Fn()&&r0(t,s),t.Ja.size===0&&(n.Fn()?n.Nn():jr(t)&&t.iu.set("Unknown"))}function hl(r,e){if(r.su.We(e.targetId),e.resumeToken.approximateByteSize()>0||e.snapshotVersion.compareTo(K.min())>0){const t=r.Za.get(e.targetId);if(t===void 0)return void L(Jt,"SDK target ID not found for remote ID: "+e.targetId);const n=r.remoteSyncer.getRemoteKeysForTarget(t).size;e=e.withExpectedCount(n)}Hs(r).jn(e)}function r0(r,e){r.su.We(e),Hs(r).Hn(e)}function dl(r){r.su=new m3({getRemoteKeysForTarget:e=>{const t=r.Za.get(e);return t!==void 0?r.remoteSyncer.getRemoteKeysForTarget(t):se()},dt:e=>r.Ja.get(e)||null,Tt:()=>r.datastore.serializer.databaseId}),Hs(r).start(),r.iu.Wa()}function fl(r){return jr(r)&&!Hs(r).Cn()&&r.Ja.size>0}function jr(r){return H(r).tu.size===0}function s0(r){r.su=void 0}async function a5(r){r.iu.set("Online")}async function u5(r){r.Ja.forEach((e,t)=>{hl(r,e)})}async function c5(r,e){s0(r),fl(r)?(r.iu.za(e),dl(r)):r.iu.set("Unknown")}async function l5(r,e,t){if(r.iu.set("Online"),e instanceof j6&&e.state===2&&e.cause)try{await async function(s,i){const o=i.cause;for(const u of i.targetIds){if(s.Ja.has(u)){const c=s.Za.get(u);c!==void 0&&(await s.remoteSyncer.rejectListen(c,o),s.Ya.delete(c),s.Za.delete(u)),s.Ja.delete(u)}s.su.removeTarget(u)}}(r,e)}catch(n){L(Jt,"Failed to remove targets %s: %s ",e.targetIds.join(","),n),await Ga(r,n)}else if(e instanceof da?r.su.et(e):e instanceof G6?r.su.ot(e):r.su.rt(e),!t.isEqual(K.min()))try{const n=await Qp(r.localStore);t.compareTo(n)>=0&&await function(i,o){const u=i.su.Rt(o);u.targetChanges.forEach((l,d)=>{if(l.resumeToken.approximateByteSize()>0){const g=i.Ja.get(d);g&&i.Ja.set(d,g.withResumeToken(l.resumeToken,o))}}),u.targetMismatches.forEach((l,d)=>{const g=i.Ja.get(l);if(!g)return;i.Ja.set(l,g.withResumeToken(Ce.EMPTY_BYTE_STRING,g.snapshotVersion)),r0(i,l);const y=new Ht(g.target,l,d,g.sequenceNumber);hl(i,y)});const c=function(d,g){const y=new Map;g.targetChanges.forEach((C,M)=>{const q=d.Za.get(M);q!==void 0&&y.set(q,C)});let R=new Ie(Z);return g.targetMismatches.forEach((C,M)=>{const q=d.Za.get(C);q!==void 0&&(R=R.insert(q,M))}),new Bs(g.snapshotVersion,y,R,g.documentUpdates,g.augmentedDocumentUpdates,g.resolvedLimboDocuments)}(i,u);return i.remoteSyncer.applyRemoteEvent(c)}(r,t)}catch(n){L(Jt,"Failed to raise snapshot:",n),await Ga(r,n)}}async function Ga(r,e,t){if(!nr(e))throw e;r.tu.add(1),await So(r),r.iu.set("Offline"),t||(t=()=>Qp(r.localStore)),r.asyncQueue.enqueueRetryable(async()=>{L(Jt,"Retrying IndexedDB access"),await t(),r.tu.delete(1),await Au(r)})}function i0(r,e){return e().catch(t=>Ga(r,t,e))}async function zs(r){const e=H(r),t=Zn(e);let n=e.Ha.length>0?e.Ha[e.Ha.length-1].batchId:vr;for(;h5(e);)try{const s=await s5(e.localStore,n);if(s===null){e.Ha.length===0&&t.Nn();break}n=s.batchId,d5(e,s)}catch(s){await Ga(e,s)}o0(e)&&a0(e)}function h5(r){return jr(r)&&r.Ha.length<10}function d5(r,e){r.Ha.push(e);const t=Zn(r);t.Fn()&&t.Jn&&t.Yn(e.mutations)}function o0(r){return jr(r)&&!Zn(r).Cn()&&r.Ha.length>0}function a0(r){Zn(r).start()}async function f5(r){Zn(r).er()}async function p5(r){const e=Zn(r);for(const t of r.Ha)e.Yn(t.mutations)}async function g5(r,e,t){const n=r.Ha.shift(),s=tl.from(n,e,t);await i0(r,()=>r.remoteSyncer.applySuccessfulWrite(s)),await zs(r)}async function m5(r,e){e&&Zn(r).Jn&&await async function(n,s){if(function(o){return F6(o)&&o!==D.ABORTED}(s.code)){const i=n.Ha.shift();Zn(n).Mn(),await i0(n,()=>n.remoteSyncer.rejectFailedWrite(i.batchId,s)),await zs(n)}}(r,e),o0(r)&&a0(r)}async function Df(r,e){const t=H(r);t.asyncQueue.verifyOperationInProgress(),L(Jt,"RemoteStore received new credentials");const n=jr(t);t.tu.add(3),await So(t),n&&t.iu.set("Unknown"),await t.remoteSyncer.handleCredentialChange(e),t.tu.delete(3),await Au(t)}async function s1(r,e){const t=H(r);e?(t.tu.delete(2),await Au(t)):e||(t.tu.add(2),await So(t),t.iu.set("Unknown"))}function Hs(r){return r._u||(r._u=function(t,n,s){const i=H(t);return i.nr(),new L3(n,i.connection,i.authCredentials,i.appCheckCredentials,i.serializer,s)}(r.datastore,r.asyncQueue,{Qt:a5.bind(null,r),zt:u5.bind(null,r),Ht:c5.bind(null,r),zn:l5.bind(null,r)}),r.nu.push(async e=>{e?(r._u.Mn(),fl(r)?dl(r):r.iu.set("Unknown")):(await r._u.stop(),s0(r))})),r._u}function Zn(r){return r.ou||(r.ou=function(t,n,s){const i=H(t);return i.nr(),new M3(n,i.connection,i.authCredentials,i.appCheckCredentials,i.serializer,s)}(r.datastore,r.asyncQueue,{Qt:()=>Promise.resolve(),zt:f5.bind(null,r),Ht:m5.bind(null,r),Zn:p5.bind(null,r),Xn:g5.bind(null,r)}),r.nu.push(async e=>{e?(r.ou.Mn(),await zs(r)):(await r.ou.stop(),r.Ha.length>0&&(L(Jt,`Stopping write stream with ${r.Ha.length} pending writes`),r.Ha=[]))})),r.ou}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class pl{constructor(e,t,n,s,i){this.asyncQueue=e,this.timerId=t,this.targetTimeMs=n,this.op=s,this.removalCallback=i,this.deferred=new xt,this.then=this.deferred.promise.then.bind(this.deferred.promise),this.deferred.promise.catch(o=>{})}get promise(){return this.deferred.promise}static createAndSchedule(e,t,n,s,i){const o=Date.now()+n,u=new pl(e,t,o,s,i);return u.start(n),u}start(e){this.timerHandle=setTimeout(()=>this.handleDelayElapsed(),e)}skipDelay(){return this.handleDelayElapsed()}cancel(e){this.timerHandle!==null&&(this.clearTimeout(),this.deferred.reject(new F(D.CANCELLED,"Operation cancelled"+(e?": "+e:""))))}handleDelayElapsed(){this.asyncQueue.enqueueAndForget(()=>this.timerHandle!==null?(this.clearTimeout(),this.op().then(e=>this.deferred.resolve(e))):Promise.resolve())}clearTimeout(){this.timerHandle!==null&&(this.removalCallback(this),clearTimeout(this.timerHandle),this.timerHandle=null)}}function gl(r,e){if(ke("AsyncQueue",`${e}: ${r}`),nr(r))return new F(D.UNAVAILABLE,`${e}: ${r}`);throw r}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Pr{static emptySet(e){return new Pr(e.comparator)}constructor(e){this.comparator=e?(t,n)=>e(t,n)||$.comparator(t.key,n.key):(t,n)=>$.comparator(t.key,n.key),this.keyedMap=mr(),this.sortedSet=new Ie(this.comparator)}has(e){return this.keyedMap.get(e)!=null}get(e){return this.keyedMap.get(e)}first(){return this.sortedSet.minKey()}last(){return this.sortedSet.maxKey()}isEmpty(){return this.sortedSet.isEmpty()}indexOf(e){const t=this.keyedMap.get(e);return t?this.sortedSet.indexOf(t):-1}get size(){return this.sortedSet.size}forEach(e){this.sortedSet.inorderTraversal((t,n)=>(e(t),!1))}add(e){const t=this.delete(e.key);return t.copy(t.keyedMap.insert(e.key,e),t.sortedSet.insert(e,null))}delete(e){const t=this.get(e);return t?this.copy(this.keyedMap.remove(e),this.sortedSet.remove(t)):this}isEqual(e){if(!(e instanceof Pr)||this.size!==e.size)return!1;const t=this.sortedSet.getIterator(),n=e.sortedSet.getIterator();for(;t.hasNext();){const s=t.getNext().key,i=n.getNext().key;if(!s.isEqual(i))return!1}return!0}toString(){const e=[];return this.forEach(t=>{e.push(t.toString())}),e.length===0?"DocumentSet ()":`DocumentSet (
  `+e.join(`  
`)+`
)`}copy(e,t){const n=new Pr;return n.comparator=this.comparator,n.keyedMap=e,n.sortedSet=t,n}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Of{constructor(){this.au=new Ie($.comparator)}track(e){const t=e.doc.key,n=this.au.get(t);n?e.type!==0&&n.type===3?this.au=this.au.insert(t,e):e.type===3&&n.type!==1?this.au=this.au.insert(t,{type:n.type,doc:e.doc}):e.type===2&&n.type===2?this.au=this.au.insert(t,{type:2,doc:e.doc}):e.type===2&&n.type===0?this.au=this.au.insert(t,{type:0,doc:e.doc}):e.type===1&&n.type===0?this.au=this.au.remove(t):e.type===1&&n.type===2?this.au=this.au.insert(t,{type:1,doc:n.doc}):e.type===0&&n.type===1?this.au=this.au.insert(t,{type:2,doc:e.doc}):j(63341,{ft:e,uu:n}):this.au=this.au.insert(t,e)}cu(){const e=[];return this.au.inorderTraversal((t,n)=>{e.push(n)}),e}}class Cs{constructor(e,t,n,s,i,o,u,c,l){this.query=e,this.docs=t,this.oldDocs=n,this.docChanges=s,this.mutatedKeys=i,this.fromCache=o,this.syncStateChanged=u,this.excludesMetadataChanges=c,this.hasCachedResults=l}static fromInitialDocuments(e,t,n,s,i){const o=[];return t.forEach(u=>{o.push({type:0,doc:u})}),new Cs(e,t,Pr.emptySet(t),o,n,s,!0,!1,i)}get hasPendingWrites(){return!this.mutatedKeys.isEmpty()}isEqual(e){if(!(this.fromCache===e.fromCache&&this.hasCachedResults===e.hasCachedResults&&this.syncStateChanged===e.syncStateChanged&&this.mutatedKeys.isEqual(e.mutatedKeys)&&_u(this.query,e.query)&&this.docs.isEqual(e.docs)&&this.oldDocs.isEqual(e.oldDocs)))return!1;const t=this.docChanges,n=e.docChanges;if(t.length!==n.length)return!1;for(let s=0;s<t.length;s++)if(t[s].type!==n[s].type||!t[s].doc.isEqual(n[s].doc))return!1;return!0}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class _5{constructor(){this.lu=void 0,this.Eu=[]}hu(){return this.Eu.some(e=>e.Tu())}}class y5{constructor(){this.queries=kf(),this.onlineState="Unknown",this.Pu=new Set}terminate(){(function(t,n){const s=H(t),i=s.queries;s.queries=kf(),i.forEach((o,u)=>{for(const c of u.Eu)c.onError(n)})})(this,new F(D.ABORTED,"Firestore shutting down"))}}function kf(){return new En(r=>Vp(r),_u)}async function ml(r,e){const t=H(r);let n=3;const s=e.query;let i=t.queries.get(s);i?!i.hu()&&e.Tu()&&(n=2):(i=new _5,n=e.Tu()?0:1);try{switch(n){case 0:i.lu=await t.onListen(s,!0);break;case 1:i.lu=await t.onListen(s,!1);break;case 2:await t.onFirstRemoteStoreListen(s)}}catch(o){const u=gl(o,`Initialization of query '${xe(e.query)?cn(e.query):Oi(e.query)}' failed`);return void e.onError(u)}t.queries.set(s,i),i.Eu.push(e),e.Ru(t.onlineState),i.lu&&e.Iu(i.lu)&&yl(t)}async function _l(r,e){const t=H(r),n=e.query;let s=3;const i=t.queries.get(n);if(i){const o=i.Eu.indexOf(e);o>=0&&(i.Eu.splice(o,1),i.Eu.length===0?s=e.Tu()?0:1:!i.hu()&&e.Tu()&&(s=2))}switch(s){case 0:return t.queries.delete(n),t.onUnlisten(n,!0);case 1:return t.queries.delete(n),t.onUnlisten(n,!1);case 2:return t.onLastRemoteStoreUnlisten(n);default:return}}function E5(r,e){const t=H(r);let n=!1;for(const s of e){const i=s.query,o=t.queries.get(i);if(o){for(const u of o.Eu)u.Iu(s)&&(n=!0);o.lu=s}}n&&yl(t)}function I5(r,e,t){const n=H(r),s=n.queries.get(e);if(s)for(const i of s.Eu)i.onError(t);n.queries.delete(e)}function yl(r){r.Pu.forEach(e=>{e.next()})}var i1;(function(r){r.Default="default",r.Cache="cache"})(i1||(i1={}));class El{constructor(e,t,n){this.query=e,this.Au=t,this.Vu=!1,this.du=null,this.onlineState="Unknown",this.options=n||{}}Iu(e){if(!this.options.includeMetadataChanges){const n=[];for(const s of e.docChanges)s.type!==3&&n.push(s);e=new Cs(e.query,e.docs,e.oldDocs,n,e.mutatedKeys,e.fromCache,e.syncStateChanged,!0,e.hasCachedResults)}let t=!1;return this.Vu?this.fu(e)&&(this.Au.next(e),t=!0):this.mu(e,this.onlineState)&&(this.pu(e),t=!0),this.du=e,t}onError(e){this.Au.error(e)}Ru(e){this.onlineState=e;let t=!1;return this.du&&!this.Vu&&this.mu(this.du,e)&&(this.pu(this.du),t=!0),t}mu(e,t){if(!e.fromCache||!this.Tu())return!0;const n=t!=="Offline";return(!this.options.waitForSyncWhenOnline||!n)&&(!e.docs.isEmpty()||e.hasCachedResults||t==="Offline")}fu(e){if(e.docChanges.length>0)return!0;const t=this.du&&this.du.hasPendingWrites!==e.hasPendingWrites;return!(!e.syncStateChanged&&!t)&&this.options.includeMetadataChanges===!0}pu(e){e=Cs.fromInitialDocuments(e.query,e.docs,e.mutatedKeys,e.fromCache,e.hasCachedResults),this.Vu=!0,this.Au.next(e)}Tu(){return this.options.source!==i1.Cache}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class u0{constructor(e){this.key=e}}class c0{constructor(e){this.key=e}}class w5{constructor(e,t){this.query=e,this.Ou=t,this.Mu=null,this.hasCachedResults=!1,this.current=!1,this.Nu=se(),this.mutatedKeys=se(),this.Lu=xe(e)?Zc(e):V1(e),this.Bu=new Pr(this.Lu)}get Uu(){return this.Ou}ku(e,t){const n=t?t.qu:new Of,s=t?t.Bu:this.Bu;let i=t?t.mutatedKeys:this.mutatedKeys,o=s,u=!1;const[c,l]=this.$u(this.query,s);e.inorderTraversal((g,y)=>{const R=s.get(g),C=qp(this.query,y)?y:null,M=!!R&&this.mutatedKeys.has(R.key),q=!!C&&(C.hasLocalMutations||this.mutatedKeys.has(C.key)&&C.hasCommittedMutations);let Q=!1;R&&C?R.data.isEqual(C.data)?M!==q&&(n.track({type:3,doc:C}),Q=!0):this.Ku(R,C)||(n.track({type:2,doc:C}),Q=!0,(c&&this.Lu(C,c)>0||l&&this.Lu(C,l)<0)&&(u=!0)):!R&&C?(n.track({type:0,doc:C}),Q=!0):R&&!C&&(n.track({type:1,doc:R}),Q=!0,(c||l)&&(u=!0)),Q&&(C?(o=o.add(C),i=q?i.add(g):i.delete(g)):(o=o.delete(g),i=i.delete(g)))});const d=this.Wu(this.query);if(d)if(xe(this.query)){const g=[];o.forEach(C=>g.push(C));const y=Bp(this.query,g);let R=new Pr(Zc(this.query));for(const C of y)R=R.add(C);o.forEach(C=>{R.has(C.key)||(i=i.delete(C.key),n.track({type:1,doc:C}))}),o=R}else{const g=this.Qu(this.query);for(;o.size>d;){const y=g==="F"?o.last():o.first();o=o.delete(y.key),i=i.delete(y.key),n.track({type:1,doc:y})}}return{Bu:o,qu:n,Uo:u,mutatedKeys:i}}Wu(e){var t;return xe(e)?(t=hc(e))==null?void 0:t.limit:e.limit||void 0}Qu(e){if(xe(e)){const t=hc(e);return t&&t.limit<0?"L":"F"}return e.limitType}$u(e,t){var n;if(xe(e)){const s=(n=hc(e))==null?void 0:n.limit;return[t.size===s?t.last():null,null]}return[e.limitType==="F"&&t.size===this.Wu(this.query)?t.last():null,e.limitType==="L"&&t.size===this.Wu(this.query)?t.first():null]}Ku(e,t){return e.hasLocalMutations&&t.hasCommittedMutations&&!t.hasLocalMutations}applyChanges(e,t,n,s){const i=this.Bu;this.Bu=e.Bu,this.mutatedKeys=e.mutatedKeys;const o=e.qu.cu();o.sort((d,g)=>function(R,C){const M=q=>{switch(q){case 0:return 1;case 2:case 3:return 2;case 1:return 0;default:return j(20277,{ft:q})}};return M(R)-M(C)}(d.type,g.type)||this.Lu(d.doc,g.doc)),this.Gu(n),s=s??!1;const u=t&&!s?this.zu():[],c=this.Nu.size===0&&this.current&&!s?1:0,l=c!==this.Mu;return this.Mu=c,o.length!==0||l?{snapshot:new Cs(this.query,e.Bu,i,o,e.mutatedKeys,c===0,l,!1,!!n&&n.resumeToken.approximateByteSize()>0),ju:u}:{ju:u}}Ru(e){return this.current&&e==="Offline"?(this.current=!1,this.applyChanges({Bu:this.Bu,qu:new Of,mutatedKeys:this.mutatedKeys,Uo:!1},!1)):{ju:[]}}Hu(e){return!this.Ou.has(e)&&!!this.Bu.has(e)&&!this.Bu.get(e).hasLocalMutations}Gu(e){e&&(e.addedDocuments.forEach(t=>this.Ou=this.Ou.add(t)),e.modifiedDocuments.forEach(t=>{}),e.removedDocuments.forEach(t=>this.Ou=this.Ou.delete(t)),this.current=e.current)}zu(){if(!this.current)return[];const e=this.Nu;this.Nu=se(),this.Bu.forEach(n=>{this.Hu(n.key)&&(this.Nu=this.Nu.add(n.key))});const t=[];return e.forEach(n=>{this.Nu.has(n)||t.push(new c0(n))}),this.Nu.forEach(n=>{e.has(n)||t.push(new u0(n))}),t}Ju(e){this.Ou=e.Jo,this.Nu=se();const t=this.ku(e.documents);return this.applyChanges(t,!0)}Yu(){return Cs.fromInitialDocuments(this.query,this.Bu,this.mutatedKeys,this.Mu===0,this.hasCachedResults)}}const Ks="SyncEngine";class T5{constructor(e,t,n){this.query=e,this.targetId=t,this.view=n}}class A5{constructor(e){this.key=e,this.Zu=!1}}class v5{constructor(e,t,n,s,i,o){this.localStore=e,this.remoteStore=t,this.eventManager=n,this.sharedClientState=s,this.currentUser=i,this.maxConcurrentLimboResolutions=o,this.Xu={},this.ec=new En(u=>Vp(u),_u),this.tc=new Map,this.nc=new Set,this.rc=new Ie($.comparator),this.sc=new Map,this._c=new il,this.oc={},this.ac=new Map,this.uc=gn.Cs(),this.onlineState="Unknown",this.cc=void 0}get isPrimaryClient(){return this.cc===!0}}async function R5(r,e,t=!0){const n=Ru(r);let s;const i=n.ec.get(e);return i?(n.sharedClientState.addLocalQueryTarget(i.targetId),s=i.view.Yu()):s=await l0(n,e,t,!0),s}async function S5(r,e){const t=Ru(r);await l0(t,e,!0,!1)}async function l0(r,e,t,n){const s=await Ba(r.localStore,xe(e)?e:wt(e)),i=s.targetId,o=r.sharedClientState.addLocalQueryTarget(i,t);let u;return n&&(u=await Il(r,e,i,o==="current",s.resumeToken)),r.isPrimaryClient&&t&&vu(r.remoteStore,s),u}async function Il(r,e,t,n,s){r.lc=(g,y,R)=>async function(M,q,Q,te){let ne=q.view.ku(Q);ne.Uo&&(ne=await e1(M.localStore,q.query,!1).then(({documents:A})=>q.view.ku(A,ne)));const Te=te&&te.targetChanges.get(q.targetId),de=te&&te.targetMismatches.get(q.targetId)!=null,fe=q.view.applyChanges(ne,M.isPrimaryClient,Te,de);return o1(M,q.targetId,fe.ju),fe.snapshot}(r,g,y,R);const i=await e1(r.localStore,e,!0),o=new w5(e,i.Jo),u=o.ku(i.documents),c=Eo.createSynthesizedTargetChangeForCurrentChange(t,n&&r.onlineState!=="Offline",s),l=o.applyChanges(u,r.isPrimaryClient,c);o1(r,t,l.ju);const d=new T5(e,t,o);return r.ec.set(e,d),r.tc.has(t)?r.tc.get(t).push(e):r.tc.set(t,[e]),l.snapshot}async function P5(r,e,t){const n=H(r),s=n.ec.get(e),i=n.tc.get(s.targetId);if(i.length>1)return n.tc.set(s.targetId,i.filter(o=>!_u(o,e))),void n.ec.delete(e);n.isPrimaryClient?(n.sharedClientState.removeLocalQueryTarget(s.targetId),n.sharedClientState.isActiveQueryTarget(s.targetId)||await Ps(n.localStore,s.targetId,!1).then(()=>{n.sharedClientState.clearQueryState(s.targetId),t&&bs(n.remoteStore,s.targetId),Ns(n,s.targetId)}).catch(tr)):(Ns(n,s.targetId),await Ps(n.localStore,s.targetId,!0))}async function b5(r,e){const t=H(r),n=t.ec.get(e),s=t.tc.get(n.targetId);t.isPrimaryClient&&s.length===1&&(t.sharedClientState.removeLocalQueryTarget(n.targetId),bs(t.remoteStore,n.targetId))}async function C5(r,e,t){const n=vl(r);try{const s=await function(o,u){const c=H(o),l=me.now(),d=u.reduce((R,C)=>R.add(C.key),se());let g,y;return c.persistence.runTransaction("Locally write mutations","readwrite",R=>{let C=Be(),M=se();return c.Qo.getEntries(R,d).next(q=>{C=q,C.forEach((Q,te)=>{te.isValidDocument()||(M=M.add(Q))})}).next(()=>c.localDocuments.getOverlayedDocuments(R,C)).next(q=>{g=q;const Q=[];for(const te of u){const ne=Q4(te,g.get(te.key).overlayedDocument);ne!=null&&Q.push(new yn(te.key,ne,T6(ne.value.mapValue),Se.exists(!0)))}return c.mutationQueue.addMutationBatch(R,l,Q,u)}).next(q=>{y=q;const Q=q.applyToLocalDocumentSet(g,M);return c.documentOverlayCache.saveOverlays(R,q.batchId,Q)})}).then(()=>({batchId:y.batchId,changes:q6(g)}))}(n.localStore,e);n.sharedClientState.addPendingMutation(s.batchId),function(o,u,c){let l=o.oc[o.currentUser.toKey()];l||(l=new Ie(Z)),l=l.insert(u,c),o.oc[o.currentUser.toKey()]=l}(n,s.batchId,t),await ir(n,s.changes),await zs(n.remoteStore)}catch(s){const i=gl(s,"Failed to persist write");t.reject(i)}}async function h0(r,e){const t=H(r);try{const n=await n5(t.localStore,e);e.targetChanges.forEach((s,i)=>{const o=t.sc.get(i);o&&(U(s.addedDocuments.size+s.modifiedDocuments.size+s.removedDocuments.size<=1,22616),s.addedDocuments.size>0?o.Zu=!0:s.modifiedDocuments.size>0?U(o.Zu,14607):s.removedDocuments.size>0&&(U(o.Zu,42227),o.Zu=!1))}),await ir(t,n,e)}catch(n){await tr(n)}}function Lf(r,e,t){const n=H(r);if(n.isPrimaryClient&&t===0||!n.isPrimaryClient&&t===1){const s=[];n.ec.forEach((i,o)=>{const u=o.view.Ru(e);u.snapshot&&s.push(u.snapshot)}),function(o,u){const c=H(o);c.onlineState=u;let l=!1;c.queries.forEach((d,g)=>{for(const y of g.Eu)y.Ru(u)&&(l=!0)}),l&&yl(c)}(n.eventManager,e),s.length&&n.Xu.zn(s),n.onlineState=e,n.isPrimaryClient&&n.sharedClientState.setOnlineState(e)}}async function N5(r,e,t){const n=H(r);n.sharedClientState.updateQueryState(e,"rejected",t);const s=n.sc.get(e),i=s&&s.key;if(i){let o=new Ie($.comparator);o=o.insert(i,Re.newNoDocument(i,K.min()));const u=se().add(i),c=new Bs(K.min(),new Map,new Ie(Z),o,Be(),u);await h0(n,c),n.rc=n.rc.remove(i),n.sc.delete(e),Al(n)}else await Ps(n.localStore,e,!1).then(()=>Ns(n,e,t)).catch(tr)}async function V5(r,e){const t=H(r),n=e.batch.batchId;try{const s=await t5(t.localStore,e);Tl(t,n,null),wl(t,n),t.sharedClientState.updateMutationState(n,"acknowledged"),await ir(t,s)}catch(s){await tr(s)}}async function x5(r,e,t){const n=H(r);try{const s=await function(o,u){const c=H(o);return c.persistence.runTransaction("Reject batch","readwrite-primary",l=>{let d;return c.mutationQueue.lookupMutationBatch(l,u).next(g=>(U(g!==null,37113),d=g.keys(),c.mutationQueue.removeMutationBatch(l,g))).next(()=>c.mutationQueue.performConsistencyCheck(l)).next(()=>c.documentOverlayCache.removeOverlaysForBatchId(l,d,u)).next(()=>c.localDocuments.recalculateAndSaveOverlaysForDocumentKeys(l,d)).next(()=>c.localDocuments.getDocuments(l,d))})}(n.localStore,e);Tl(n,e,t),wl(n,e),n.sharedClientState.updateMutationState(e,"rejected",t),await ir(n,s)}catch(s){await tr(s)}}function wl(r,e){(r.ac.get(e)||[]).forEach(t=>{t.resolve()}),r.ac.delete(e)}function Tl(r,e,t){const n=H(r);let s=n.oc[n.currentUser.toKey()];if(s){const i=s.get(e);i&&(t?i.reject(t):i.resolve(),s=s.remove(e)),n.oc[n.currentUser.toKey()]=s}}function Ns(r,e,t=null){r.sharedClientState.removeLocalQueryTarget(e);for(const n of r.tc.get(e))r.ec.delete(n),t&&r.Xu.Ec(n,t);r.tc.delete(e),r.isPrimaryClient&&r._c.s_(e).forEach(n=>{r._c.containsKey(n)||d0(r,n)})}function d0(r,e){r.nc.delete(e.path.canonicalString());const t=r.rc.get(e);t!==null&&(bs(r.remoteStore,t),r.rc=r.rc.remove(e),r.sc.delete(t),Al(r))}function o1(r,e,t){for(const n of t)n instanceof u0?(r._c.addReference(n.key,e),D5(r,n)):n instanceof c0?(L(Ks,"Document no longer in limbo: "+n.key),r._c.removeReference(n.key,e),r._c.containsKey(n.key)||d0(r,n.key)):j(19791,{hc:n})}function D5(r,e){const t=e.key,n=t.path.canonicalString();r.rc.get(t)||r.nc.has(n)||(L(Ks,"New document in limbo: "+t),r.nc.add(n),Al(r))}function Al(r){for(;r.nc.size>0&&r.rc.size<r.maxConcurrentLimboResolutions;){const e=r.nc.values().next().value;r.nc.delete(e);const t=new $(ae.fromString(e)),n=r.uc.next();r.sc.set(n,new A5(t)),r.rc=r.rc.insert(t,n),vu(r.remoteStore,new Ht(wt(yo(t.path)),n,"TargetPurposeLimboResolution",ht.ce))}}async function ir(r,e,t){const n=H(r),s=[],i=[],o=[];n.ec.isEmpty()||(n.ec.forEach((u,c)=>{o.push(n.lc(c,e,t).then(l=>{var d;if((l||t)&&n.isPrimaryClient){const g=l?!l.fromCache:(d=t==null?void 0:t.targetChanges.get(c.targetId))==null?void 0:d.current;n.sharedClientState.updateQueryState(c.targetId,g?"current":"not-current")}if(l){s.push(l);const g=ul.vo(c.targetId,l);i.push(g)}}))}),await Promise.all(o),n.Xu.zn(s),await async function(c,l){const d=H(c);try{await d.persistence.runTransaction("notifyLocalViewChanges","readwrite",g=>P.forEach(l,y=>P.forEach(y.wo,R=>d.persistence.referenceDelegate.addReference(g,y.targetId,R)).next(()=>P.forEach(y.bo,R=>d.persistence.referenceDelegate.removeReference(g,y.targetId,R)))))}catch(g){if(!nr(g))throw g;L(cl,"Failed to update sequence numbers: "+g)}for(const g of l){const y=g.targetId;if(!g.fromCache){const R=d.$o.get(y),C=R.snapshotVersion,M=R.withLastLimboFreeSnapshotVersion(C);d.$o=d.$o.insert(y,M)}}}(n.localStore,i))}async function O5(r,e){const t=H(r);if(!t.currentUser.isEqual(e)){L(Ks,"User change. New user:",e.toKey());const n=await Wp(t.localStore,e);t.currentUser=e,function(i,o){i.ac.forEach(u=>{u.forEach(c=>{c.reject(new F(D.CANCELLED,o))})}),i.ac.clear()}(t,"'waitForPendingWrites' promise is rejected due to a user change."),t.sharedClientState.handleUserChange(e,n.removedBatchIds,n.addedBatchIds),await ir(t,n.zo)}}function k5(r,e){const t=H(r),n=t.sc.get(e);if(n&&n.Zu)return se().add(n.key);{let s=se();const i=t.tc.get(e);if(!i)return s;for(const o of i??[]){const u=t.ec.get(o);s=s.unionWith(u.view.Uu)}return s}}async function L5(r,e){const t=H(r),n=await e1(t.localStore,e.query,!0),s=e.view.Ju(n);return t.isPrimaryClient&&o1(t,e.targetId,s.ju),s}async function M5(r,e){const t=H(r);return t1(t.localStore,e).then(n=>ir(t,n))}async function F5(r,e,t,n){const s=H(r),i=await function(u,c){const l=H(u),d=H(l.mutationQueue);return l.persistence.runTransaction("Lookup mutation documents","readonly",g=>d.ps(g,c).next(y=>y?l.localDocuments.getDocuments(g,y):P.resolve(null)))}(s.localStore,e);i!==null?(t==="pending"?await zs(s.remoteStore):t==="acknowledged"||t==="rejected"?(Tl(s,e,n||null),wl(s,e),function(u,c){H(H(u).mutationQueue).bs(c)}(s.localStore,e)):j(6720,"Unknown batchState",{Tc:t}),await ir(s,i)):L(Ks,"Cannot apply mutation batch with id: "+e)}async function U5(r,e){const t=H(r);if(Ru(t),vl(t),e===!0&&t.cc!==!0){const n=t.sharedClientState.getAllActiveQueryTargets(),s=await Mf(t,n.toArray());t.cc=!0,await s1(t.remoteStore,!0);for(const i of s)vu(t.remoteStore,i)}else if(e===!1&&t.cc!==!1){const n=[];let s=Promise.resolve();t.tc.forEach((i,o)=>{t.sharedClientState.isLocalQueryTarget(o)?n.push(o):s=s.then(()=>(Ns(t,o),Ps(t.localStore,o,!0))),bs(t.remoteStore,o)}),await s,await Mf(t,n),function(o){const u=H(o);u.sc.forEach((c,l)=>{bs(u.remoteStore,l)}),u._c.__(),u.sc=new Map,u.rc=new Ie($.comparator)}(t),t.cc=!1,await s1(t.remoteStore,!1)}}async function Mf(r,e,t){const n=H(r),s=[],i=[];for(const o of e){let u;const c=n.tc.get(o);if(c&&c.length!==0){u=await Ba(n.localStore,xe(c[0])?c[0]:wt(c[0]));for(const l of c){const d=n.ec.get(l),g=await L5(n,d);g.snapshot&&i.push(g.snapshot)}}else{const l=await Yp(n.localStore,o);u=await Ba(n.localStore,l),await Il(n,f0(l),o,!1,u.resumeToken)}s.push(u)}return n.Xu.zn(i),s}function f0(r){return nn(r)?r:L6(r.path,r.collectionGroup,r.orderBy,r.filters,r.limit,"F",r.startAt,r.endAt)}function B5(r){return function(t){return H(H(t).persistence).po()}(H(r).localStore)}async function q5(r,e,t,n){const s=H(r);if(s.cc)return void L(Ks,"Ignoring unexpected query state notification.");const i=s.tc.get(e);if(i&&i.length>0)switch(t){case"current":case"not-current":{let o;if(xe(i[0]))switch(un(i[0])){case"collection_group":case"collection":o=await t1(s.localStore,vp(i[0]));break;case"documents":o=await function(l,d){const g=H(l),y=se(...Oa(d).map(R=>$.fromPath(R)));return g.persistence.runTransaction("Get documents for pipeline","readonly",R=>g.Qo.getEntries(R,y)).then(R=>R)}(s.localStore,i[0]);break;default:Lt(""),o=mr()}else o=await t1(s.localStore,function(l){return l.collectionGroup||(l.path.length%2==1?l.path.lastSegment():l.path.get(l.path.length-2))}(i[0]));const u=Bs.createSynthesizedRemoteEventForCurrentChange(e,t==="current",Ce.EMPTY_BYTE_STRING);await ir(s,o,u);break}case"rejected":await Ps(s.localStore,e,!0),Ns(s,e,n);break;default:j(64155,t)}}async function $5(r,e,t){const n=Ru(r);if(n.cc){for(const s of e){if(n.tc.has(s)&&n.sharedClientState.isActiveQueryTarget(s)){L(Ks,"Adding an already active target "+s);continue}const i=await Yp(n.localStore,s),o=await Ba(n.localStore,i);await Il(n,f0(i),o.targetId,!1,o.resumeToken),vu(n.remoteStore,o)}for(const s of t)n.tc.has(s)&&await Ps(n.localStore,s,!1).then(()=>{bs(n.remoteStore,s),Ns(n,s)}).catch(tr)}}function Ru(r){const e=H(r);return e.remoteStore.remoteSyncer.applyRemoteEvent=h0.bind(null,e),e.remoteStore.remoteSyncer.getRemoteKeysForTarget=k5.bind(null,e),e.remoteStore.remoteSyncer.rejectListen=N5.bind(null,e),e.Xu.zn=E5.bind(null,e.eventManager),e.Xu.Ec=I5.bind(null,e.eventManager),e}function vl(r){const e=H(r);return e.remoteStore.remoteSyncer.applySuccessfulWrite=V5.bind(null,e),e.remoteStore.remoteSyncer.rejectFailedWrite=x5.bind(null,e),e}class ao{constructor(){this.kind="memory",this.synchronizeTabs=!1}async initialize(e){this.serializer=du(e.databaseInfo.databaseId),this.sharedClientState=this.Rc(e),this.persistence=this.Ic(e),await this.persistence.start(),this.localStore=this.Ac(e),this.gcScheduler=this.Vc(e,this.localStore),this.indexBackfillerScheduler=this.dc(e,this.localStore)}Vc(e,t){return null}dc(e,t){return null}Ac(e){return Kp(this.persistence,new Hp,e.initialUser,this.serializer)}Ic(e){return new ol(Tu.C_,this.serializer)}Rc(e){return new t0}async terminate(){var e,t;(e=this.gcScheduler)==null||e.stop(),(t=this.indexBackfillerScheduler)==null||t.stop(),this.sharedClientState.shutdown(),await this.persistence.shutdown()}}ao.provider={build:()=>new ao};class G5 extends ao{constructor(e){super(),this.cacheSizeBytes=e}Vc(e,t){U(this.persistence.referenceDelegate instanceof Ua,46915);const n=this.persistence.referenceDelegate.garbageCollector;return new cp(n,e.asyncQueue,t)}Ic(e){const t=this.cacheSizeBytes!==void 0?tt.withCacheSize(this.cacheSizeBytes):tt.DEFAULT;return new ol(n=>Ua.C_(n,t),this.serializer)}}class p0 extends ao{constructor(e,t,n){super(),this.fc=e,this.cacheSizeBytes=t,this.forceOwnership=n,this.kind="persistent",this.synchronizeTabs=!1}async initialize(e){await super.initialize(e),await this.fc.initialize(this,e),await vl(this.fc.syncEngine),await zs(this.fc.remoteStore),await this.persistence._o(()=>(this.gcScheduler&&!this.gcScheduler.started&&this.gcScheduler.start(),this.indexBackfillerScheduler&&!this.indexBackfillerScheduler.started&&this.indexBackfillerScheduler.start(),Promise.resolve()))}Ac(e){return Kp(this.persistence,new Hp,e.initialUser,this.serializer)}Vc(e,t){const n=this.persistence.referenceDelegate.garbageCollector;return new cp(n,e.asyncQueue,t)}dc(e,t){const n=new c4(t,this.persistence);return new u4(e.asyncQueue,n)}Ic(e){const t=zp(e.databaseInfo.databaseId,e.databaseInfo.persistenceKey),n=this.cacheSizeBytes!==void 0?tt.withCacheSize(this.cacheSizeBytes):tt.DEFAULT;return new al(this.synchronizeTabs,t,e.clientId,n,e.asyncQueue,n0(),ma(),this.serializer,this.sharedClientState,!!this.forceOwnership)}Rc(e){return new t0}}class j5 extends p0{constructor(e,t){super(e,t,!1),this.fc=e,this.cacheSizeBytes=t,this.synchronizeTabs=!0}async initialize(e){await super.initialize(e);const t=this.fc.syncEngine;this.sharedClientState instanceof mc&&(this.sharedClientState.syncEngine={Na:F5.bind(null,t),La:q5.bind(null,t),Ba:$5.bind(null,t),po:B5.bind(null,t),Ma:M5.bind(null,t)},await this.sharedClientState.start()),await this.persistence._o(async n=>{await U5(this.fc.syncEngine,n),this.gcScheduler&&(n&&!this.gcScheduler.started?this.gcScheduler.start():n||this.gcScheduler.stop()),this.indexBackfillerScheduler&&(n&&!this.indexBackfillerScheduler.started?this.indexBackfillerScheduler.start():n||this.indexBackfillerScheduler.stop())})}Rc(e){const t=n0();if(!mc.C(t))throw new F(D.UNIMPLEMENTED,"IndexedDB persistence is only available on platforms that support LocalStorage.");const n=zp(e.databaseInfo.databaseId,e.databaseInfo.persistenceKey);return new mc(t,e.asyncQueue,n,e.clientId,e.initialUser)}}class uo{async initialize(e,t){this.localStore||(this.localStore=e.localStore,this.sharedClientState=e.sharedClientState,this.datastore=this.createDatastore(t),this.remoteStore=this.createRemoteStore(t),this.eventManager=this.createEventManager(t),this.syncEngine=this.createSyncEngine(t,!e.synchronizeTabs),this.sharedClientState.onlineStateHandler=n=>Lf(this.syncEngine,n,1),this.remoteStore.remoteSyncer.handleCredentialChange=O5.bind(null,this.syncEngine),await s1(this.remoteStore,this.syncEngine.isPrimaryClient))}createEventManager(e){return function(){return new y5}()}createDatastore(e){const t=du(e.databaseInfo.databaseId),n=k3(e.databaseInfo);return B3(e.authCredentials,e.appCheckCredentials,n,t)}createRemoteStore(e){return function(n,s,i,o,u){return new o5(n,s,i,o,u)}(this.localStore,this.datastore,e.asyncQueue,t=>Lf(this.syncEngine,t,0),function(){return nf.C()?new nf:new V3}())}createSyncEngine(e,t){return function(s,i,o,u,c,l,d){const g=new v5(s,i,o,u,c,l);return d&&(g.cc=!0),g}(this.localStore,this.remoteStore,this.eventManager,this.sharedClientState,e.initialUser,e.maxConcurrentLimboResolutions,t)}async terminate(){var e,t;await async function(s){const i=H(s);L(Jt,"RemoteStore shutting down."),i.tu.add(5),await So(i),i.ru.shutdown(),i.iu.set("Unknown")}(this.remoteStore),(e=this.datastore)==null||e.terminate(),(t=this.eventManager)==null||t.terminate()}}uo.provider={build:()=>new uo};/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *//**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Rl{constructor(e){this.observer=e,this.muted=!1}next(e){this.muted||this.observer.next&&this.mc(this.observer.next,e)}error(e){this.muted||(this.observer.error?this.mc(this.observer.error,e):ke("Uncaught Error in snapshot listener:",e.toString()))}gc(){this.muted=!0}mc(e,t){setTimeout(()=>{this.muted||e(t)},0)}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */let z5=class{constructor(e){this.datastore=e,this.readVersions=new Map,this.mutations=[],this.committed=!1,this.lastTransactionError=null,this.writtenDocs=new Set}async lookup(e){if(this.ensureCommitNotCalled(),this.mutations.length>0)throw this.lastTransactionError=new F(D.INVALID_ARGUMENT,"Firestore transactions require all reads to be executed before all writes."),this.lastTransactionError;const t=await async function(s,i){const o=H(s),u={documents:i.map(g=>Ss(o.serializer,g))},c=await o.$t("BatchGetDocuments",o.serializer.databaseId,ae.emptyPath(),u,i.length),l=new Map;c.forEach(g=>{const y=T3(o.serializer,g);l.set(y.key.toString(),y)});const d=[];return i.forEach(g=>{const y=l.get(g.toString());U(!!y,55234,{key:g}),d.push(y)}),d}(this.datastore,e);return t.forEach(n=>this.recordVersion(n)),t}set(e,t){this.write(t.toMutation(e,this.precondition(e))),this.writtenDocs.add(e.toString())}update(e,t){try{this.write(t.toMutation(e,this.preconditionForUpdate(e)))}catch(n){this.lastTransactionError=n}this.writtenDocs.add(e.toString())}delete(e){this.write(new Fs(e,this.precondition(e))),this.writtenDocs.add(e.toString())}async commit(){if(this.ensureCommitNotCalled(),this.lastTransactionError)throw this.lastTransactionError;const e=this.readVersions;this.mutations.forEach(t=>{e.delete(t.key.toString())}),e.forEach((t,n)=>{const s=$.fromPath(n);this.mutations.push(new P1(s,this.precondition(s)))}),await async function(n,s){const i=H(n),o={writes:s.map(u=>eo(i.serializer,u))};await i.Bt("Commit",i.serializer.databaseId,ae.emptyPath(),o)}(this.datastore,this.mutations),this.committed=!0}recordVersion(e){let t;if(e.isFoundDocument())t=e.version;else{if(!e.isNoDocument())throw j(50498,{Oc:e.constructor.name});t=K.min()}const n=this.readVersions.get(e.key.toString());if(n){if(!t.isEqual(n))throw new F(D.ABORTED,"Document version changed between two reads.")}else this.readVersions.set(e.key.toString(),t)}precondition(e){const t=this.readVersions.get(e.toString());return!this.writtenDocs.has(e.toString())&&t?t.isEqual(K.min())?Se.exists(!1):Se.updateTime(t):Se.none()}preconditionForUpdate(e){const t=this.readVersions.get(e.toString());if(!this.writtenDocs.has(e.toString())&&t){if(t.isEqual(K.min()))throw new F(D.INVALID_ARGUMENT,"Can't update a document that doesn't exist.");return Se.updateTime(t)}return Se.exists(!0)}write(e){this.ensureCommitNotCalled(),this.mutations.push(e)}ensureCommitNotCalled(){}};/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class H5{constructor(e,t,n,s,i){this.asyncQueue=e,this.datastore=t,this.options=n,this.updateFunction=s,this.deferred=i,this.Mc=n.maxAttempts,this.xn=new M1(this.asyncQueue,"transaction_retry")}Nc(){this.Mc-=1,this.Lc()}Lc(){this.xn.mn(async()=>{const e=new z5(this.datastore),t=this.Bc(e);t&&t.then(n=>{this.asyncQueue.enqueueAndForget(()=>e.commit().then(()=>{this.deferred.resolve(n)}).catch(s=>{this.Uc(s)}))}).catch(n=>{this.Uc(n)})})}Bc(e){try{const t=this.updateFunction(e);return!go(t)&&t.catch&&t.then?t:(this.deferred.reject(Error("Transaction callback must return a Promise")),null)}catch(t){return this.deferred.reject(t),null}}Uc(e){this.Mc>0&&this.kc(e)?(this.Mc-=1,this.asyncQueue.enqueueAndForget(()=>(this.Lc(),Promise.resolve()))):this.deferred.reject(e)}kc(e){if((e==null?void 0:e.name)==="FirebaseError"){const t=e.code;return t==="aborted"||t==="failed-precondition"||t==="already-exists"||!F6(t)}return!1}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const er="FirestoreClient";class K5{constructor(e,t,n,s,i){this.authCredentials=e,this.appCheckCredentials=t,this.asyncQueue=n,this._databaseInfo=s,this.user=et.UNAUTHENTICATED,this.clientId=g1.newId(),this.authCredentialListener=()=>Promise.resolve(),this.appCheckCredentialListener=()=>Promise.resolve(),this._uninitializedComponentsProvider=i,this.authCredentials.start(n,async o=>{L(er,"Received user=",o.uid),await this.authCredentialListener(o),this.user=o}),this.appCheckCredentials.start(n,o=>(L(er,"Received new app check token=",o),this.appCheckCredentialListener(o,this.user)))}get configuration(){return{asyncQueue:this.asyncQueue,databaseInfo:this._databaseInfo,clientId:this.clientId,authCredentials:this.authCredentials,appCheckCredentials:this.appCheckCredentials,initialUser:this.user,maxConcurrentLimboResolutions:100}}setCredentialChangeListener(e){this.authCredentialListener=e}setAppCheckTokenChangeListener(e){this.appCheckCredentialListener=e}terminate(){this.asyncQueue.enterRestrictedMode();const e=new xt;return this.asyncQueue.enqueueAndForgetEvenWhileRestricted(async()=>{try{this._onlineComponents&&await this._onlineComponents.terminate(),this._offlineComponents&&await this._offlineComponents.terminate(),this.authCredentials.shutdown(),this.appCheckCredentials.shutdown(),e.resolve()}catch(t){const n=gl(t,"Failed to shutdown persistence");e.reject(n)}}),e.promise}}async function _c(r,e){r.asyncQueue.verifyOperationInProgress(),L(er,"Initializing OfflineComponentProvider");const t=r.configuration;await e.initialize(t);let n=t.initialUser;r.setCredentialChangeListener(async s=>{n.isEqual(s)||(await Wp(e.localStore,s),n=s)}),e.persistence.setDatabaseDeletedListener(()=>r.terminate()),r._offlineComponents=e}async function Ff(r,e){r.asyncQueue.verifyOperationInProgress();const t=await W5(r);L(er,"Initializing OnlineComponentProvider"),await e.initialize(t,r.configuration),r.setCredentialChangeListener(n=>Df(e.remoteStore,n)),r.setAppCheckTokenChangeListener((n,s)=>Df(e.remoteStore,s)),r._onlineComponents=e}async function W5(r){if(!r._offlineComponents)if(r._uninitializedComponentsProvider){L(er,"Using user provided OfflineComponentProvider");try{await _c(r,r._uninitializedComponentsProvider._offline)}catch(e){const t=e;if(!function(s){return s.name==="FirebaseError"?s.code===D.FAILED_PRECONDITION||s.code===D.UNIMPLEMENTED:!(typeof DOMException<"u"&&s instanceof DOMException)||s.code===22||s.code===20||s.code===11}(t))throw t;Lt("Error using user provided cache. Falling back to memory cache: "+t),await _c(r,new ao)}}else L(er,"Using default OfflineComponentProvider"),await _c(r,new G5(void 0));return r._offlineComponents}async function Sl(r){return r._onlineComponents||(r._uninitializedComponentsProvider?(L(er,"Using user provided OnlineComponentProvider"),await Ff(r,r._uninitializedComponentsProvider._online)):(L(er,"Using default OnlineComponentProvider"),await Ff(r,new uo))),r._onlineComponents}function Q5(r){return Sl(r).then(e=>e.syncEngine)}function Y5(r){return Sl(r).then(e=>e.datastore)}async function ja(r){const e=await Sl(r),t=e.eventManager;return t.onListen=R5.bind(null,e.syncEngine),t.onUnlisten=P5.bind(null,e.syncEngine),t.onFirstRemoteStoreListen=S5.bind(null,e.syncEngine),t.onLastRemoteStoreUnlisten=b5.bind(null,e.syncEngine),t}function X5(r,e,t,n){const s=new Rl(n),i=new El(e,s,t);return r.asyncQueue.enqueueAndForget(async()=>ml(await ja(r),i)),()=>{s.gc(),r.asyncQueue.enqueueAndForget(async()=>_l(await ja(r),i))}}function J5(r,e,t={}){const n=new xt;return r.asyncQueue.enqueueAndForget(async()=>function(i,o,u,c,l){const d=new Rl({next:y=>{d.gc(),o.enqueueAndForget(()=>_l(i,g));const R=y.docs.has(u);!R&&y.fromCache?l.reject(new F(D.UNAVAILABLE,"Failed to get document because the client is offline.")):R&&y.fromCache&&c&&c.source==="server"?l.reject(new F(D.UNAVAILABLE,'Failed to get document from server. (However, this document does exist in the local cache. Run again without setting source to "server" to retrieve the cached document.)')):l.resolve(y)},error:y=>l.reject(y)}),g=new El(yo(u.path),d,{includeMetadataChanges:!0,waitForSyncWhenOnline:!0});return ml(i,g)}(await ja(r),r.asyncQueue,e,t,n)),n.promise}function Z5(r,e,t={}){const n=new xt;return r.asyncQueue.enqueueAndForget(async()=>function(i,o,u,c,l){const d=new Rl({next:y=>{d.gc(),o.enqueueAndForget(()=>_l(i,g)),y.fromCache&&c.source==="server"?l.reject(new F(D.UNAVAILABLE,'Failed to get documents from server. (However, these documents may exist in the local cache. Run again without setting source to "server" to retrieve the cached documents.)')):l.resolve(y)},error:y=>l.reject(y)}),g=new El(u instanceof Li?A8(u):u,d,{includeMetadataChanges:!0,waitForSyncWhenOnline:!0});return ml(i,g)}(await ja(r),r.asyncQueue,e,t,n)),n.promise}function e_(r,e){const t=new xt;return r.asyncQueue.enqueueAndForget(async()=>C5(await Q5(r),e,t)),t.promise}function t_(r,e,t){const n=new xt;return r.asyncQueue.enqueueAndForget(async()=>{const s=await Y5(r);new H5(r.asyncQueue,s,t,e,n).Nc()}),n.promise}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Uf="AsyncQueue";class Bf{constructor(e=Promise.resolve()){this.qc=[],this.$c=!1,this.Kc=[],this.Wc=null,this.Qc=!1,this.Gc=!1,this.zc=[],this.xn=new M1(this,"async_queue_retry"),this.jc=()=>{const n=ma();n&&L(Uf,"Visibility state changed to "+n.visibilityState),this.xn.gn()},this.Hc=e;const t=ma();t&&typeof t.addEventListener=="function"&&t.addEventListener("visibilitychange",this.jc)}get isShuttingDown(){return this.$c}enqueueAndForget(e){this.enqueue(e)}enqueueAndForgetEvenWhileRestricted(e){this.Jc(),this.Yc(e)}enterRestrictedMode(e){if(!this.$c){this.$c=!0,this.Gc=e||!1;const t=ma();t&&typeof t.removeEventListener=="function"&&t.removeEventListener("visibilitychange",this.jc)}}enqueue(e){if(this.Jc(),this.$c)return new Promise(()=>{});const t=new xt;return this.Yc(()=>this.$c&&this.Gc?Promise.resolve():(e().then(t.resolve,t.reject),t.promise)).then(()=>t.promise)}enqueueRetryable(e){this.enqueueAndForget(()=>(this.qc.push(e),this.Zc()))}async Zc(){if(this.qc.length!==0){try{await this.qc[0](),this.qc.shift(),this.xn.reset()}catch(e){if(!nr(e))throw e;L(Uf,"Operation failed with retryable error: "+e)}this.qc.length>0&&this.xn.mn(()=>this.Zc())}}Yc(e){const t=this.Hc.then(()=>(this.Qc=!0,e().catch(n=>{throw this.Wc=n,this.Qc=!1,ke("INTERNAL UNHANDLED ERROR: ",qf(n)),n}).then(n=>(this.Qc=!1,n))));return this.Hc=t,t}enqueueAfterDelay(e,t,n){this.Jc(),this.zc.indexOf(e)>-1&&(t=0);const s=pl.createAndSchedule(this,e,t,n,i=>this.Xc(i));return this.Kc.push(s),s}Jc(){this.Wc&&j(47125,{el:qf(this.Wc)})}verifyOperationInProgress(){}async tl(){let e;do e=this.Hc,await e;while(e!==this.Hc)}nl(e){for(const t of this.Kc)if(t.timerId===e)return!0;return!1}rl(e){return this.tl().then(()=>{this.Kc.sort((t,n)=>t.targetTimeMs-n.targetTimeMs);for(const t of this.Kc)if(t.skipDelay(),e!=="all"&&t.timerId===e)break;return this.tl()})}il(e){this.zc.push(e)}Xc(e){const t=this.Kc.indexOf(e);this.Kc.splice(t,1)}}function qf(r){let e=r.message||"";return r.stack&&(e=r.stack.includes(r.message)?r.stack:r.message+`
`+r.stack),e}class Mt extends F1{constructor(e,t,n,s){super(e,t,n,s),this.type="firestore",this._queue=new Bf,this._persistenceKey=(s==null?void 0:s.name)||"[DEFAULT]"}async _terminate(){if(this._firestoreClient){const e=this._firestoreClient.terminate();this._queue=new Bf(e),this._firestoreClient=void 0,await e}}}function sw(r,e,t){t||(t=Ca);const n=Os(r,"firestore");if(n.isInitialized(t)){const s=n.getImmediate({identifier:t}),i=n.getOptions(t);if(Nr(i,e))return s;throw new F(D.FAILED_PRECONDITION,"initializeFirestore() has already been called with different options. To avoid this error, call initializeFirestore() with the same options as when it was originally called, or call getFirestore() to return the already initialized instance.")}if(e.cacheSizeBytes!==void 0&&e.localCache!==void 0)throw new F(D.INVALID_ARGUMENT,"cache and cacheSizeBytes cannot be specified at the same time as cacheSizeBytes willbe deprecated. Instead, specify the cache size in the cache object");if(e.cacheSizeBytes!==void 0&&e.cacheSizeBytes!==-1&&e.cacheSizeBytes<up)throw new F(D.INVALID_ARGUMENT,"cacheSizeBytes must be at least 1048576");return e.host&&ho(e.host)&&k2(e.host),n.initialize({options:e,instanceIdentifier:t})}function Ws(r){if(r._terminated)throw new F(D.FAILED_PRECONDITION,"The client has already been terminated.");return r._firestoreClient||n_(r),r._firestoreClient}function n_(r){var n,s,i,o;const e=r._freezeSettings(),t=$3(r._databaseId,((n=r._app)==null?void 0:n.options.appId)||"",r._persistenceKey,(s=r._app)==null?void 0:s.options.apiKey,e);r._componentsProvider||(i=e.localCache)!=null&&i._offlineComponentProvider&&((o=e.localCache)!=null&&o._onlineComponentProvider)&&(r._componentsProvider={_offline:e.localCache._offlineComponentProvider,_online:e.localCache._onlineComponentProvider}),r._firestoreClient=new K5(r._authCredentials,r._appCheckCredentials,r._queue,t,r._componentsProvider&&function(c){const l=c==null?void 0:c._online.build();return{_offline:c==null?void 0:c._offline.build(l),_online:l}}(r._componentsProvider))}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class g0{convertValue(e,t="none"){switch(Fe(e)){case 0:return null;case 1:return e.booleanValue;case 2:return we(e.integerValue||e.doubleValue);case 3:return this.convertTimestamp(e.timestampValue);case 4:return this.convertServerTimestamp(e,t);case 5:return e.stringValue;case 6:return this.convertBytes(dn(e.bytesValue));case 7:return this.convertReference(e.referenceValue);case 8:return this.convertGeoPoint(e.geoPointValue);case 9:return this.convertArray(e.arrayValue,t);case 11:return this.convertObject(e.mapValue,t);case 10:return this.convertVectorValue(e.mapValue);default:throw j(62114,{value:e})}}convertObject(e,t){return this.convertObjectMap(e.fields,t)}convertObjectMap(e,t="none"){const n={};return rr(e,(s,i)=>{n[s]=this.convertValue(i,t)}),n}convertVectorValue(e){var n,s,i;const t=(i=(s=(n=e.fields)==null?void 0:n[Dr].arrayValue)==null?void 0:s.values)==null?void 0:i.map(o=>we(o.doubleValue));return new ft(t)}convertGeoPoint(e){return new Wt(we(e.latitude),we(e.longitude))}convertArray(e,t){return(e.values||[]).map(n=>this.convertValue(n,t))}convertServerTimestamp(e,t){switch(t){case"previous":const n=_o(e);return n==null?null:this.convertValue(n,t);case"estimate":return this.convertTimestamp(ys(e));default:return null}}convertTimestamp(e){const t=hn(e);return new me(t.seconds,t.nanos)}convertDocumentKey(e,t){const n=ae.fromString(e);U(np(n),9688,{name:e});const s=new xr(n.get(1),n.get(3)),i=new $(n.popFirst(5));return s.isEqual(t)||ke(`Document ${i} contains a document reference within a different database (${s.projectId}/${s.database}) which is not supported. It will be treated as a reference in the current database (${t.projectId}/${t.database}) instead.`),i}}/**
 * @license
 * Copyright 2024 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Su extends g0{constructor(e){super(),this.firestore=e}convertBytes(e){return new Et(e)}convertReference(e){const t=this.convertDocumentKey(e,this.firestore._databaseId);return new Pe(this.firestore,null,t)}}const $f="@firebase/firestore",Gf="4.16.0";/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function jf(r){return function(t,n){if(typeof t!="object"||t===null)return!1;const s=t;for(const i of n)if(i in s&&typeof s[i]=="function")return!0;return!1}(r,["next","error","complete"])}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class za{constructor(e,t,n,s,i){this._firestore=e,this._userDataWriter=t,this._key=n,this._document=s,this._converter=i}get id(){return this._key.path.lastSegment()}get ref(){return new Pe(this._firestore,this._converter,this._key)}exists(){return this._document!==null}data(){if(this._document){if(this._converter){const e=new r_(this._firestore,this._userDataWriter,this._key,this._document,null);return this._converter.fromFirestore(e)}return this._userDataWriter.convertValue(this._document.data.value)}}_fieldsProto(){var e;return((e=this._document)==null?void 0:e.data.clone().value.mapValue.fields)??void 0}get(e){if(this._document){const t=this._document.data.field(fn("DocumentSnapshot.get",e));if(t!==null)return this._userDataWriter.convertValue(t)}}}class r_ extends za{data(){return super.data()}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function m0(r){if(r.limitType==="L"&&r.explicitOrderBy.length===0)throw new F(D.UNIMPLEMENTED,"limitToLast() queries require specifying at least one orderBy() clause")}class Pl{}class _0 extends Pl{}function iw(r,e,...t){let n=[];e instanceof Pl&&n.push(e),n=n.concat(t),function(i){const o=i.filter(c=>c instanceof bl).length,u=i.filter(c=>c instanceof Pu).length;if(o>1||o>0&&u>0)throw new F(D.INVALID_ARGUMENT,"InvalidQuery. When using composite filters, you cannot use more than one filter at the top level. Consider nesting the multiple filters within an `and(...)` statement. For example: change `query(query, where(...), or(...))` to `query(query, and(where(...), or(...)))`.")}(n);for(const s of n)r=s._apply(r);return r}class Pu extends _0{constructor(e,t,n){super(),this._field=e,this._op=t,this._value=n,this.type="where"}static _create(e,t,n){return new Pu(e,t,n)}_apply(e){const t=this._parse(e);return y0(e._query,t),new sr(e.firestore,e.converter,$c(e._query,t))}_parse(e){const t=$s(e.firestore);return function(i,o,u,c,l,d,g){let y;if(l.isKeyField()){if(d==="array-contains"||d==="array-contains-any")throw new F(D.INVALID_ARGUMENT,`Invalid Query. You can't perform '${d}' queries on documentId().`);if(d==="in"||d==="not-in"){Hf(g,d);const C=[];for(const M of g)C.push(zf(c,i,M));y={arrayValue:{values:C}}}else y=zf(c,i,g)}else d!=="in"&&d!=="not-in"&&d!=="array-contains-any"||Hf(g,d),y=Y3(u,o,g,d==="in"||d==="not-in");return ce.create(l,d,y)}(e._query,"where",t,e.firestore._databaseId,this._field,this._op,this._value)}}function ow(r,e,t){const n=e,s=fn("where",r);return Pu._create(s,n,t)}class bl extends Pl{constructor(e,t){super(),this.type=e,this._queryConstraints=t}static _create(e,t){return new bl(e,t)}_parse(e){const t=this._queryConstraints.map(n=>n._parse(e)).filter(n=>n.getFilters().length>0);return t.length===1?t[0]:_e.create(t,this._getOperator())}_apply(e){const t=this._parse(e);return t.getFilters().length===0?e:(function(s,i){let o=s;const u=i.getFlattenedFilters();for(const c of u)y0(o,c),o=$c(o,c)}(e._query,t),new sr(e.firestore,e.converter,$c(e._query,t)))}_getQueryConstraints(){return this._queryConstraints}_getOperator(){return this.type==="and"?"and":"or"}}class Cl extends _0{constructor(e,t){super(),this._field=e,this._direction=t,this.type="orderBy"}static _create(e,t){return new Cl(e,t)}_apply(e){const t=function(s,i,o){if(s.startAt!==null)throw new F(D.INVALID_ARGUMENT,"Invalid query. You must not call startAt() or startAfter() before calling orderBy().");if(s.endAt!==null)throw new F(D.INVALID_ARGUMENT,"Invalid query. You must not call endAt() or endBefore() before calling orderBy().");return new Zi(i,o)}(e._query,this._field,this._direction);return new sr(e.firestore,e.converter,o3(e._query,t))}}function aw(r,e="asc"){const t=e,n=fn("orderBy",r);return Cl._create(n,t)}function zf(r,e,t){if(typeof(t=be(t))=="string"){if(t==="")throw new F(D.INVALID_ARGUMENT,"Invalid query. When querying with documentId(), you must provide a valid document ID, but it was an empty string.");if(!M6(e)&&t.indexOf("/")!==-1)throw new F(D.INVALID_ARGUMENT,`Invalid query. When querying a collection by documentId(), you must provide a plain document ID, but '${t}' contains a '/' character.`);const n=e.path.child(ae.fromString(t));if(!$.isDocumentKey(n))throw new F(D.INVALID_ARGUMENT,`Invalid query. When querying a collection group by documentId(), the value provided must result in a valid document path, but '${n}' is not because it has an odd number of segments (${n.length}).`);return Qi(r,new $(n))}if(t instanceof Pe)return Qi(r,t._key);throw new F(D.INVALID_ARGUMENT,`Invalid query. When querying with documentId(), you must provide a valid string or a DocumentReference, but it was: ${nu(t)}.`)}function Hf(r,e){if(!Array.isArray(r)||r.length===0)throw new F(D.INVALID_ARGUMENT,`Invalid Query. A non-empty array is required for '${e.toString()}' filters.`)}function y0(r,e){const t=function(s,i){for(const o of s)for(const u of o.getFlattenedFilters())if(i.indexOf(u.op)>=0)return u.op;return null}(r.filters,function(s){switch(s){case"!=":return["!=","not-in"];case"array-contains-any":case"in":return["not-in"];case"not-in":return["array-contains-any","in","not-in","!="];default:return[]}}(e.op));if(t!==null)throw t===e.op?new F(D.INVALID_ARGUMENT,`Invalid query. You cannot use more than one '${e.op.toString()}' filter.`):new F(D.INVALID_ARGUMENT,`Invalid query. You cannot use '${e.op.toString()}' filters with '${t.toString()}' filters.`)}function bu(r,e,t){let n;return n=r?t&&(t.merge||t.mergeFields)?r.toFirestore(e,t):r.toFirestore(e):e,n}class s_ extends g0{constructor(e){super(),this.firestore=e}convertBytes(e){return new Et(e)}convertReference(e){const t=this.convertDocumentKey(e,this.firestore._databaseId);return new Pe(this.firestore,null,t)}}class i_{constructor(e){let t;this.kind="persistent",e!=null&&e.tabManager?(e.tabManager._initialize(e),t=e.tabManager):(t=u_(void 0),t._initialize(e)),this._onlineComponentProvider=t._onlineComponentProvider,this._offlineComponentProvider=t._offlineComponentProvider}toJSON(){return{kind:this.kind}}}function uw(r){return new i_(r)}class o_{constructor(e){this.forceOwnership=e,this.kind="persistentSingleTab"}toJSON(){return{kind:this.kind}}_initialize(e){this._onlineComponentProvider=uo.provider,this._offlineComponentProvider={build:t=>new p0(t,e==null?void 0:e.cacheSizeBytes,this.forceOwnership)}}}class a_{constructor(){this.kind="PersistentMultipleTab"}toJSON(){return{kind:this.kind}}_initialize(e){this._onlineComponentProvider=uo.provider,this._offlineComponentProvider={build:t=>new j5(t,e==null?void 0:e.cacheSizeBytes)}}}function u_(r){return new o_(r==null?void 0:r.forceOwnership)}function cw(){return new a_}class is{constructor(e,t){this.hasPendingWrites=e,this.fromCache=t}isEqual(e){return this.hasPendingWrites===e.hasPendingWrites&&this.fromCache===e.fromCache}}class zn extends za{constructor(e,t,n,s,i,o){super(e,t,n,s,o),this._firestore=e,this._firestoreImpl=e,this.metadata=i}exists(){return super.exists()}data(e={}){if(this._document){if(this._converter){const t=new _a(this._firestore,this._userDataWriter,this._key,this._document,this.metadata,null);return this._converter.fromFirestore(t,e)}return this._userDataWriter.convertValue(this._document.data.value,e.serverTimestamps)}}get(e,t={}){if(this._document){const n=this._document.data.field(fn("DocumentSnapshot.get",e));if(n!==null)return this._userDataWriter.convertValue(n,t.serverTimestamps)}}toJSON(){if(this.metadata.hasPendingWrites)throw new F(D.FAILED_PRECONDITION,"DocumentSnapshot.toJSON() attempted to serialize a document with pending writes. Await waitForPendingWrites() before invoking toJSON().");const e=this._document,t={};return t.type=zn._jsonSchemaVersion,t.bundle="",t.bundleSource="DocumentSnapshot",t.bundleName=this._key.toString(),!e||!e.isValidDocument()||!e.isFoundDocument()?t:(this._userDataWriter.convertObjectMap(e.data.value.mapValue.fields,"previous"),t.bundle=(this._firestore,this.ref.path,"NOT SUPPORTED"),t)}}zn._jsonSchemaVersion="firestore/documentSnapshot/1.0",zn._jsonSchema={type:Me("string",zn._jsonSchemaVersion),bundleSource:Me("string","DocumentSnapshot"),bundleName:Me("string"),bundle:Me("string")};class _a extends zn{data(e={}){return super.data(e)}}class br{constructor(e,t,n,s){this._firestore=e,this._userDataWriter=t,this._snapshot=s,this.metadata=new is(s.hasPendingWrites,s.fromCache),this.query=n}get docs(){const e=[];return this.forEach(t=>e.push(t)),e}get size(){return this._snapshot.docs.size}get empty(){return this.size===0}forEach(e,t){this._snapshot.docs.forEach(n=>{e.call(t,new _a(this._firestore,this._userDataWriter,n.key,n,new is(this._snapshot.mutatedKeys.has(n.key),this._snapshot.fromCache),this.query.converter))})}docChanges(e={}){const t=!!e.includeMetadataChanges;if(t&&this._snapshot.excludesMetadataChanges)throw new F(D.INVALID_ARGUMENT,"To include metadata changes with your document changes, you must also pass { includeMetadataChanges:true } to onSnapshot().");return this._cachedChanges&&this._cachedChangesIncludeMetadataChanges===t||(this._cachedChanges=function(s,i){if(s._snapshot.oldDocs.isEmpty()){let o=0;return s._snapshot.docChanges.map(u=>{xe(s._snapshot.query)?Zc(s._snapshot.query):V1(s.query._query);const c=new _a(s._firestore,s._userDataWriter,u.doc.key,u.doc,new is(s._snapshot.mutatedKeys.has(u.doc.key),s._snapshot.fromCache),s.query.converter);return u.doc,{type:"added",doc:c,oldIndex:-1,newIndex:o++}})}{let o=s._snapshot.oldDocs;return s._snapshot.docChanges.filter(u=>i||u.type!==3).map(u=>{const c=new _a(s._firestore,s._userDataWriter,u.doc.key,u.doc,new is(s._snapshot.mutatedKeys.has(u.doc.key),s._snapshot.fromCache),s.query.converter);let l=-1,d=-1;return u.type!==0&&(l=o.indexOf(u.doc.key),o=o.delete(u.doc.key)),u.type!==1&&(o=o.add(u.doc),d=o.indexOf(u.doc.key)),{type:c_(u.type),doc:c,oldIndex:l,newIndex:d}})}}(this,t),this._cachedChangesIncludeMetadataChanges=t),this._cachedChanges}toJSON(){if(this.metadata.hasPendingWrites)throw new F(D.FAILED_PRECONDITION,"QuerySnapshot.toJSON() attempted to serialize a document with pending writes. Await waitForPendingWrites() before invoking toJSON().");const e={};e.type=br._jsonSchemaVersion,e.bundleSource="QuerySnapshot",e.bundleName=g1.newId(),this._firestore._databaseId.database,this._firestore._databaseId.projectId;const t=[],n=[],s=[];return this.docs.forEach(i=>{i._document!==null&&(t.push(i._document),n.push(this._userDataWriter.convertObjectMap(i._document.data.value.mapValue.fields,"previous")),s.push(i.ref.path))}),e.bundle=(this._firestore,this.query._query,e.bundleName,"NOT SUPPORTED"),e}}function c_(r){switch(r){case 0:return"added";case 2:case 3:return"modified";case 1:return"removed";default:return j(61501,{type:r})}}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */br._jsonSchemaVersion="firestore/querySnapshot/1.0",br._jsonSchema={type:Me("string",br._jsonSchemaVersion),bundleSource:Me("string","QuerySnapshot"),bundleName:Me("string"),bundle:Me("string")};const l_={maxAttempts:5};/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class h_{constructor(e,t){this._firestore=e,this._commitHandler=t,this._mutations=[],this._committed=!1,this._dataReader=$s(e)}set(e,t,n){this._verifyNotCommitted();const s=Un(e,this._firestore),i=bu(s.converter,t,n),o=fu(this._dataReader,"WriteBatch.set",s._key,i,s.converter!==null,n);return this._mutations.push(o.toMutation(s._key,Se.none())),this}update(e,t,n,...s){this._verifyNotCommitted();const i=Un(e,this._firestore);let o;return o=typeof(t=be(t))=="string"||t instanceof qs?q1(this._dataReader,"WriteBatch.update",i._key,t,n,s):B1(this._dataReader,"WriteBatch.update",i._key,t),this._mutations.push(o.toMutation(i._key,Se.exists(!0))),this}delete(e){this._verifyNotCommitted();const t=Un(e,this._firestore);return this._mutations=this._mutations.concat(new Fs(t._key,Se.none())),this}commit(){return this._verifyNotCommitted(),this._committed=!0,this._mutations.length>0?this._commitHandler(this._mutations):Promise.resolve()}_verifyNotCommitted(){if(this._committed)throw new F(D.FAILED_PRECONDITION,"A write batch can no longer be used after commit() has been called.")}}function Un(r,e){if((r=be(r)).firestore!==e)throw new F(D.INVALID_ARGUMENT,"Provided document reference is from a different Firestore instance.");return r}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class d_{constructor(e,t){this._firestore=e,this._transaction=t,this._dataReader=$s(e)}get(e){const t=Un(e,this._firestore),n=new s_(this._firestore);return this._transaction.lookup([t._key]).then(s=>{if(!s||s.length!==1)return j(24041);const i=s[0];if(i.isFoundDocument())return new za(this._firestore,n,i.key,i,t.converter);if(i.isNoDocument())return new za(this._firestore,n,t._key,null,t.converter);throw j(18433,{doc:i})})}set(e,t,n){const s=Un(e,this._firestore),i=bu(s.converter,t,n),o=fu(this._dataReader,"Transaction.set",s._key,i,s.converter!==null,n);return this._transaction.set(s._key,o),this}update(e,t,n,...s){const i=Un(e,this._firestore);let o;return o=typeof(t=be(t))=="string"||t instanceof qs?q1(this._dataReader,"Transaction.update",i._key,t,n,s):B1(this._dataReader,"Transaction.update",i._key,t),this._transaction.update(i._key,o),this}delete(e){const t=Un(e,this._firestore);return this._transaction.delete(t._key),this}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class f_ extends d_{constructor(e,t){super(e,t),this._firestore=e}get(e){const t=Un(e,this._firestore),n=new Su(this._firestore);return super.get(e).then(s=>new zn(this._firestore,n,t._key,s._document,new is(!1,!1),t.converter))}}function lw(r,e,t){r=ct(r,Mt);const n={...l_,...t};(function(o){if(o.maxAttempts<1)throw new F(D.INVALID_ARGUMENT,"Max attempts must be at least 1")})(n);const s=Ws(r);return t_(s,i=>e(new f_(r,i)),n)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function hw(r){r=ct(r,Pe);const e=ct(r.firestore,Mt),t=Ws(e);return J5(t,r._key).then(n=>E0(e,r,n))}function dw(r){r=ct(r,sr);const e=ct(r.firestore,Mt),t=Ws(e),n=new Su(e);return m0(r._query),Z5(t,r._query).then(s=>new br(e,n,r,s))}function fw(r,e,t){r=ct(r,Pe);const n=ct(r.firestore,Mt),s=bu(r.converter,e,t),i=$s(n);return Po(n,[fu(i,"setDoc",r._key,s,r.converter!==null,t).toMutation(r._key,Se.none())])}function pw(r,e,t,...n){r=ct(r,Pe);const s=ct(r.firestore,Mt),i=$s(s);let o;return o=typeof(e=be(e))=="string"||e instanceof qs?q1(i,"updateDoc",r._key,e,t,n):B1(i,"updateDoc",r._key,e),Po(s,[o.toMutation(r._key,Se.exists(!0))])}function gw(r){return Po(ct(r.firestore,Mt),[new Fs(r._key,Se.none())])}function mw(r,e){const t=ct(r.firestore,Mt),n=H3(r),s=bu(r.converter,e),i=$s(r.firestore);return Po(t,[fu(i,"addDoc",n._key,s,r.converter!==null,{}).toMutation(n._key,Se.exists(!1))]).then(()=>n)}function _w(r,...e){var l,d,g;r=be(r);let t={includeMetadataChanges:!1,source:"default"},n=0;typeof e[n]!="object"||jf(e[n])||(t=e[n++]);const s={includeMetadataChanges:t.includeMetadataChanges,source:t.source};if(jf(e[n])){const y=e[n];e[n]=(l=y.next)==null?void 0:l.bind(y),e[n+1]=(d=y.error)==null?void 0:d.bind(y),e[n+2]=(g=y.complete)==null?void 0:g.bind(y)}let i,o,u;if(r instanceof Pe)o=ct(r.firestore,Mt),u=yo(r._key.path),i={next:y=>{e[n]&&e[n](E0(o,r,y))},error:e[n+1],complete:e[n+2]};else{const y=ct(r,sr);o=ct(y.firestore,Mt),u=y._query;const R=new Su(o);i={next:C=>{e[n]&&e[n](new br(o,R,y,C))},error:e[n+1],complete:e[n+2]},m0(r._query)}const c=Ws(o);return X5(c,u,s,i)}function Po(r,e){const t=Ws(r);return e_(t,e)}function E0(r,e,t){const n=t.docs.get(e._key),s=new Su(r);return new zn(r,s,e._key,n,new is(t.hasPendingWrites,t.fromCache),e.converter)}function yw(r){return r=ct(r,Mt),Ws(r),new h_(r,e=>Po(r,e))}(function(e,t=!0){Wm(ks),Yt(new kt("firestore",(n,{instanceIdentifier:s,options:i})=>{const o=n.getProvider("app").getImmediate(),u=new Mt(new Xm(n.getProvider("auth-internal")),new e4(o,n.getProvider("app-check-internal")),B4(o,s),o);return i={useFetchStreams:t,...i},u._setSettings(i),u},"PUBLIC").setMultipleInstances(!0)),Rt($f,Gf,e),Rt($f,Gf,"esm2020")})();var p_="firebase",g_="12.15.0";/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */Rt(p_,g_,"app");function I0(){return{"dependent-sdk-initialized-before-auth":"Another Firebase SDK was initialized and is trying to use Auth before Auth is initialized. Please be sure to call `initializeAuth` or `getAuth` before starting any other Firebase SDK."}}const m_=I0,w0=new Br("auth","Firebase",I0());/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Ha=new d1("@firebase/auth");function __(r,...e){Ha.logLevel<=ue.WARN&&Ha.warn(`Auth (${ks}): ${r}`,...e)}function ya(r,...e){Ha.logLevel<=ue.ERROR&&Ha.error(`Auth (${ks}): ${r}`,...e)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Zt(r,...e){throw Vl(r,...e)}function Dt(r,...e){return Vl(r,...e)}function Nl(r,e,t){const n={...m_(),[e]:t};return new Br("auth","Firebase",n).create(e,{appName:r.name})}function Cr(r){return Nl(r,"operation-not-supported-in-this-environment","Operations that alter the current user are not supported in conjunction with FirebaseServerApp")}function y_(r,e,t){const n=t;if(!(e instanceof n))throw n.name!==e.constructor.name&&Zt(r,"argument-error"),Nl(r,"argument-error",`Type of ${e.constructor.name} does not match expected instance.Did you pass a reference from a different Auth SDK?`)}function Vl(r,...e){if(typeof r!="string"){const t=e[0],n=[...e.slice(1)];return n[0]&&(n[0].appName=r.name),r._errorFactory.create(t,...n)}return w0.create(r,...e)}function ee(r,e,...t){if(!r)throw Vl(e,...t)}function rn(r){const e="INTERNAL ASSERTION FAILED: "+r;throw ya(e),new Error(e)}function mn(r,e){r||rn(e)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function a1(){var r;return typeof self<"u"&&((r=self.location)==null?void 0:r.href)||""}function E_(){return Kf()==="http:"||Kf()==="https:"}function Kf(){var r;return typeof self<"u"&&((r=self.location)==null?void 0:r.protocol)||null}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function I_(){return typeof navigator<"u"&&navigator&&"onLine"in navigator&&typeof navigator.onLine=="boolean"&&(E_()||Eg()||"connection"in navigator)?navigator.onLine:!0}function w_(){if(typeof navigator>"u")return null;const r=navigator;return r.languages&&r.languages[0]||r.language||null}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class bo{constructor(e,t){this.shortDelay=e,this.longDelay=t,mn(t>e,"Short delay should be less than long delay!"),this.isMobile=_g()||Ig()}get(){return I_()?this.isMobile?this.longDelay:this.shortDelay:Math.min(5e3,this.shortDelay)}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function xl(r,e){mn(r.emulator,"Emulator should always be set here");const{url:t}=r.emulator;return e?`${t}${e.startsWith("/")?e.slice(1):e}`:t}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class T0{static initialize(e,t,n){this.fetchImpl=e,t&&(this.headersImpl=t),n&&(this.responseImpl=n)}static fetch(){if(this.fetchImpl)return this.fetchImpl;if(typeof self<"u"&&"fetch"in self)return self.fetch;if(typeof globalThis<"u"&&globalThis.fetch)return globalThis.fetch;if(typeof fetch<"u")return fetch;rn("Could not find fetch implementation, make sure you call FetchProvider.initialize() with an appropriate polyfill")}static headers(){if(this.headersImpl)return this.headersImpl;if(typeof self<"u"&&"Headers"in self)return self.Headers;if(typeof globalThis<"u"&&globalThis.Headers)return globalThis.Headers;if(typeof Headers<"u")return Headers;rn("Could not find Headers implementation, make sure you call FetchProvider.initialize() with an appropriate polyfill")}static response(){if(this.responseImpl)return this.responseImpl;if(typeof self<"u"&&"Response"in self)return self.Response;if(typeof globalThis<"u"&&globalThis.Response)return globalThis.Response;if(typeof Response<"u")return Response;rn("Could not find Response implementation, make sure you call FetchProvider.initialize() with an appropriate polyfill")}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const T_={CREDENTIAL_MISMATCH:"custom-token-mismatch",MISSING_CUSTOM_TOKEN:"internal-error",INVALID_IDENTIFIER:"invalid-email",MISSING_CONTINUE_URI:"internal-error",INVALID_PASSWORD:"wrong-password",MISSING_PASSWORD:"missing-password",INVALID_LOGIN_CREDENTIALS:"invalid-credential",EMAIL_EXISTS:"email-already-in-use",PASSWORD_LOGIN_DISABLED:"operation-not-allowed",INVALID_IDP_RESPONSE:"invalid-credential",INVALID_PENDING_TOKEN:"invalid-credential",FEDERATED_USER_ID_ALREADY_LINKED:"credential-already-in-use",MISSING_REQ_TYPE:"internal-error",EMAIL_NOT_FOUND:"user-not-found",RESET_PASSWORD_EXCEED_LIMIT:"too-many-requests",EXPIRED_OOB_CODE:"expired-action-code",INVALID_OOB_CODE:"invalid-action-code",MISSING_OOB_CODE:"internal-error",CREDENTIAL_TOO_OLD_LOGIN_AGAIN:"requires-recent-login",INVALID_ID_TOKEN:"invalid-user-token",TOKEN_EXPIRED:"user-token-expired",USER_NOT_FOUND:"user-token-expired",TOO_MANY_ATTEMPTS_TRY_LATER:"too-many-requests",PASSWORD_DOES_NOT_MEET_REQUIREMENTS:"password-does-not-meet-requirements",INVALID_CODE:"invalid-verification-code",INVALID_SESSION_INFO:"invalid-verification-id",INVALID_TEMPORARY_PROOF:"invalid-credential",MISSING_SESSION_INFO:"missing-verification-id",SESSION_EXPIRED:"code-expired",MISSING_ANDROID_PACKAGE_NAME:"missing-android-pkg-name",UNAUTHORIZED_DOMAIN:"unauthorized-continue-uri",INVALID_OAUTH_CLIENT_ID:"invalid-oauth-client-id",ADMIN_ONLY_OPERATION:"admin-restricted-operation",INVALID_MFA_PENDING_CREDENTIAL:"invalid-multi-factor-session",MFA_ENROLLMENT_NOT_FOUND:"multi-factor-info-not-found",MISSING_MFA_ENROLLMENT_ID:"missing-multi-factor-info",MISSING_MFA_PENDING_CREDENTIAL:"missing-multi-factor-session",SECOND_FACTOR_EXISTS:"second-factor-already-in-use",SECOND_FACTOR_LIMIT_EXCEEDED:"maximum-second-factor-count-exceeded",BLOCKING_FUNCTION_ERROR_RESPONSE:"internal-error",RECAPTCHA_NOT_ENABLED:"recaptcha-not-enabled",MISSING_RECAPTCHA_TOKEN:"missing-recaptcha-token",INVALID_RECAPTCHA_TOKEN:"invalid-recaptcha-token",INVALID_RECAPTCHA_ACTION:"invalid-recaptcha-action",MISSING_CLIENT_TYPE:"missing-client-type",MISSING_RECAPTCHA_VERSION:"missing-recaptcha-version",INVALID_RECAPTCHA_VERSION:"invalid-recaptcha-version",INVALID_REQ_TYPE:"invalid-req-type"};/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const A_=["/v1/accounts:signInWithCustomToken","/v1/accounts:signInWithEmailLink","/v1/accounts:signInWithIdp","/v1/accounts:signInWithPassword","/v1/accounts:signInWithPhoneNumber","/v1/token"],v_=new bo(3e4,6e4);function Dl(r,e){return r.tenantId&&!e.tenantId?{...e,tenantId:r.tenantId}:e}async function Qs(r,e,t,n,s={}){return A0(r,s,async()=>{let i={},o={};n&&(e==="GET"?o=n:i={body:JSON.stringify(n)});const u=lo({...o,key:r.config.apiKey}).slice(1),c=await r._getAdditionalHeaders();c["Content-Type"]="application/json",r.languageCode&&(c["X-Firebase-Locale"]=r.languageCode);const l={method:e,headers:c,...i};return yg()||(l.referrerPolicy="strict-origin-when-cross-origin"),r.emulatorConfig&&ho(r.emulatorConfig.host)&&(l.credentials="include"),T0.fetch()(await v0(r,r.config.apiHost,t,u),l)})}async function A0(r,e,t){r._canInitEmulator=!1;const n={...T_,...e};try{const s=new S_(r),i=await Promise.race([t(),s.promise]);s.clearNetworkTimeout();const o=await i.json();if("needConfirmation"in o)throw sa(r,"account-exists-with-different-credential",o);if(i.ok&&!("errorMessage"in o))return o;{const u=i.ok?o.errorMessage:o.error.message,[c,l]=u.split(" : ");if(c==="FEDERATED_USER_ID_ALREADY_LINKED")throw sa(r,"credential-already-in-use",o);if(c==="EMAIL_EXISTS")throw sa(r,"email-already-in-use",o);if(c==="USER_DISABLED")throw sa(r,"user-disabled",o);const d=n[c]||c.toLowerCase().replace(/[_\s]+/g,"-");if(l)throw Nl(r,d,l);Zt(r,d)}}catch(s){if(s instanceof en)throw s;Zt(r,"network-request-failed",{message:String(s)})}}async function R_(r,e,t,n,s={}){const i=await Qs(r,e,t,n,s);return"mfaPendingCredential"in i&&Zt(r,"multi-factor-auth-required",{_serverResponse:i}),i}async function v0(r,e,t,n){const s=`${e}${t}?${n}`,i=r,o=i.config.emulator?xl(r.config,s):`${r.config.apiScheme}://${s}`;return A_.includes(t)&&(await i._persistenceManagerAvailable,i._getPersistenceType()==="COOKIE")?i._getPersistence()._getFinalTarget(o).toString():o}class S_{clearNetworkTimeout(){clearTimeout(this.timer)}constructor(e){this.auth=e,this.timer=null,this.promise=new Promise((t,n)=>{this.timer=setTimeout(()=>n(Dt(this.auth,"network-request-failed")),v_.get())})}}function sa(r,e,t){const n={appName:r.name};t.email&&(n.email=t.email),t.phoneNumber&&(n.phoneNumber=t.phoneNumber);const s=Dt(r,e,n);return s.customData._tokenResponse=t,s}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function P_(r,e){return Qs(r,"POST","/v1/accounts:delete",e)}async function Ka(r,e){return Qs(r,"POST","/v1/accounts:lookup",e)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Ui(r){if(r)try{const e=new Date(Number(r));if(!isNaN(e.getTime()))return e.toUTCString()}catch{}}async function b_(r,e=!1){const t=be(r),n=await t.getIdToken(e),s=Ol(n);ee(s&&s.exp&&s.auth_time&&s.iat,t.auth,"internal-error");const i=typeof s.firebase=="object"?s.firebase:void 0,o=i==null?void 0:i.sign_in_provider;return{claims:s,token:n,authTime:Ui(yc(s.auth_time)),issuedAtTime:Ui(yc(s.iat)),expirationTime:Ui(yc(s.exp)),signInProvider:o||null,signInSecondFactor:(i==null?void 0:i.sign_in_second_factor)||null}}function yc(r){return Number(r)*1e3}function Ol(r){const[e,t,n]=r.split(".");if(e===void 0||t===void 0||n===void 0)return ya("JWT malformed, contained fewer than 3 sections"),null;try{const s=P2(t);return s?JSON.parse(s):(ya("Failed to decode base64 JWT payload"),null)}catch(s){return ya("Caught error parsing JWT payload as JSON",s==null?void 0:s.toString()),null}}function Wf(r){const e=Ol(r);return ee(e,"internal-error"),ee(typeof e.exp<"u","internal-error"),ee(typeof e.iat<"u","internal-error"),Number(e.exp)-Number(e.iat)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function co(r,e,t=!1){if(t)return e;try{return await e}catch(n){throw n instanceof en&&C_(n)&&r.auth.currentUser===r&&await r.auth.signOut(),n}}function C_({code:r}){return r==="auth/user-disabled"||r==="auth/user-token-expired"}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class N_{constructor(e){this.user=e,this.isRunning=!1,this.timerId=null,this.errorBackoff=3e4}_start(){this.isRunning||(this.isRunning=!0,this.schedule())}_stop(){this.isRunning&&(this.isRunning=!1,this.timerId!==null&&clearTimeout(this.timerId))}getInterval(e){if(e){const t=this.errorBackoff;return this.errorBackoff=Math.min(this.errorBackoff*2,96e4),t}else{this.errorBackoff=3e4;const n=(this.user.stsTokenManager.expirationTime??0)-Date.now()-3e5;return Math.max(0,n)}}schedule(e=!1){if(!this.isRunning)return;const t=this.getInterval(e);this.timerId=setTimeout(async()=>{await this.iteration()},t)}async iteration(){try{await this.user.getIdToken(!0)}catch(e){(e==null?void 0:e.code)==="auth/network-request-failed"&&this.schedule(!0);return}this.schedule()}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class u1{constructor(e,t){this.createdAt=e,this.lastLoginAt=t,this._initializeTime()}_initializeTime(){this.lastSignInTime=Ui(this.lastLoginAt),this.creationTime=Ui(this.createdAt)}_copy(e){this.createdAt=e.createdAt,this.lastLoginAt=e.lastLoginAt,this._initializeTime()}toJSON(){return{createdAt:this.createdAt,lastLoginAt:this.lastLoginAt}}}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function Wa(r){var g;const e=r.auth,t=await r.getIdToken(),n=await co(r,Ka(e,{idToken:t}));ee(n==null?void 0:n.users.length,e,"internal-error");const s=n.users[0];r._notifyReloadListener(s);const i=(g=s.providerUserInfo)!=null&&g.length?R0(s.providerUserInfo):[],o=x_(r.providerData,i),u=r.isAnonymous,c=!(r.email&&s.passwordHash)&&!(o!=null&&o.length),l=u?c:!1,d={uid:s.localId,displayName:s.displayName||null,photoURL:s.photoUrl||null,email:s.email||null,emailVerified:s.emailVerified||!1,phoneNumber:s.phoneNumber||null,tenantId:s.tenantId||null,providerData:o,metadata:new u1(s.createdAt,s.lastLoginAt),isAnonymous:l};Object.assign(r,d)}async function V_(r){const e=be(r);await Wa(e),await e.auth._persistUserIfCurrent(e),e.auth._notifyListenersIfCurrent(e)}function x_(r,e){return[...r.filter(n=>!e.some(s=>s.providerId===n.providerId)),...e]}function R0(r){return r.map(({providerId:e,...t})=>({providerId:e,uid:t.rawId||"",displayName:t.displayName||null,email:t.email||null,phoneNumber:t.phoneNumber||null,photoURL:t.photoUrl||null}))}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function D_(r,e){const t=await A0(r,{},async()=>{const n=lo({grant_type:"refresh_token",refresh_token:e}).slice(1),{tokenApiHost:s,apiKey:i}=r.config,o=await v0(r,s,"/v1/token",`key=${i}`),u=await r._getAdditionalHeaders();u["Content-Type"]="application/x-www-form-urlencoded";const c={method:"POST",headers:u,body:n};return r.emulatorConfig&&ho(r.emulatorConfig.host)&&(c.credentials="include"),T0.fetch()(o,c)});return{accessToken:t.access_token,expiresIn:t.expires_in,refreshToken:t.refresh_token}}async function O_(r,e){return Qs(r,"POST","/v2/accounts:revokeToken",Dl(r,e))}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class us{constructor(){this.refreshToken=null,this.accessToken=null,this.expirationTime=null}get isExpired(){return!this.expirationTime||Date.now()>this.expirationTime-3e4}updateFromServerResponse(e){ee(e.idToken,"internal-error"),ee(typeof e.idToken<"u","internal-error"),ee(typeof e.refreshToken<"u","internal-error");const t="expiresIn"in e&&typeof e.expiresIn<"u"?Number(e.expiresIn):Wf(e.idToken);this.updateTokensAndExpiration(e.idToken,e.refreshToken,t)}updateFromIdToken(e){ee(e.length!==0,"internal-error");const t=Wf(e);this.updateTokensAndExpiration(e,null,t)}async getToken(e,t=!1){return!t&&this.accessToken&&!this.isExpired?this.accessToken:(ee(this.refreshToken,e,"user-token-expired"),this.refreshToken?(await this.refresh(e,this.refreshToken),this.accessToken):null)}clearRefreshToken(){this.refreshToken=null}async refresh(e,t){const{accessToken:n,refreshToken:s,expiresIn:i}=await D_(e,t);this.updateTokensAndExpiration(n,s,Number(i))}updateTokensAndExpiration(e,t,n){this.refreshToken=t||null,this.accessToken=e||null,this.expirationTime=Date.now()+n*1e3}static fromJSON(e,t){const{refreshToken:n,accessToken:s,expirationTime:i}=t,o=new us;return n&&(ee(typeof n=="string","internal-error",{appName:e}),o.refreshToken=n),s&&(ee(typeof s=="string","internal-error",{appName:e}),o.accessToken=s),i&&(ee(typeof i=="number","internal-error",{appName:e}),o.expirationTime=i),o}toJSON(){return{refreshToken:this.refreshToken,accessToken:this.accessToken,expirationTime:this.expirationTime}}_assign(e){this.accessToken=e.accessToken,this.refreshToken=e.refreshToken,this.expirationTime=e.expirationTime}_clone(){return Object.assign(new us,this.toJSON())}_performRefresh(){return rn("not implemented")}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Nn(r,e){ee(typeof r=="string"||typeof r>"u","internal-error",{appName:e})}class Vt{constructor({uid:e,auth:t,stsTokenManager:n,...s}){this.providerId="firebase",this.proactiveRefresh=new N_(this),this.reloadUserInfo=null,this.reloadListener=null,this.uid=e,this.auth=t,this.stsTokenManager=n,this.accessToken=n.accessToken,this.displayName=s.displayName||null,this.email=s.email||null,this.emailVerified=s.emailVerified||!1,this.phoneNumber=s.phoneNumber||null,this.photoURL=s.photoURL||null,this.isAnonymous=s.isAnonymous||!1,this.tenantId=s.tenantId||null,this.providerData=s.providerData?[...s.providerData]:[],this.metadata=new u1(s.createdAt||void 0,s.lastLoginAt||void 0)}async getIdToken(e){const t=await co(this,this.stsTokenManager.getToken(this.auth,e));return ee(t,this.auth,"internal-error"),this.accessToken!==t&&(this.accessToken=t,await this.auth._persistUserIfCurrent(this),this.auth._notifyListenersIfCurrent(this)),t}getIdTokenResult(e){return b_(this,e)}reload(){return V_(this)}_assign(e){this!==e&&(ee(this.uid===e.uid,this.auth,"internal-error"),this.displayName=e.displayName,this.photoURL=e.photoURL,this.email=e.email,this.emailVerified=e.emailVerified,this.phoneNumber=e.phoneNumber,this.isAnonymous=e.isAnonymous,this.tenantId=e.tenantId,this.providerData=e.providerData.map(t=>({...t})),this.metadata._copy(e.metadata),this.stsTokenManager._assign(e.stsTokenManager))}_clone(e){const t=new Vt({...this,auth:e,stsTokenManager:this.stsTokenManager._clone()});return t.metadata._copy(this.metadata),t}_onReload(e){ee(!this.reloadListener,this.auth,"internal-error"),this.reloadListener=e,this.reloadUserInfo&&(this._notifyReloadListener(this.reloadUserInfo),this.reloadUserInfo=null)}_notifyReloadListener(e){this.reloadListener?this.reloadListener(e):this.reloadUserInfo=e}_startProactiveRefresh(){this.proactiveRefresh._start()}_stopProactiveRefresh(){this.proactiveRefresh._stop()}async _updateTokensIfNecessary(e,t=!1){let n=!1;e.idToken&&e.idToken!==this.stsTokenManager.accessToken&&(this.stsTokenManager.updateFromServerResponse(e),n=!0),t&&await Wa(this),await this.auth._persistUserIfCurrent(this),n&&this.auth._notifyListenersIfCurrent(this)}async delete(){if(Nt(this.auth.app))return Promise.reject(Cr(this.auth));const e=await this.getIdToken();return await co(this,P_(this.auth,{idToken:e})),this.stsTokenManager.clearRefreshToken(),this.auth.signOut()}toJSON(){return{uid:this.uid,email:this.email||void 0,emailVerified:this.emailVerified,displayName:this.displayName||void 0,isAnonymous:this.isAnonymous,photoURL:this.photoURL||void 0,phoneNumber:this.phoneNumber||void 0,tenantId:this.tenantId||void 0,providerData:this.providerData.map(e=>({...e})),stsTokenManager:this.stsTokenManager.toJSON(),_redirectEventId:this._redirectEventId,...this.metadata.toJSON(),apiKey:this.auth.config.apiKey,appName:this.auth.name}}get refreshToken(){return this.stsTokenManager.refreshToken||""}static _fromJSON(e,t){const n=t.displayName??void 0,s=t.email??void 0,i=t.phoneNumber??void 0,o=t.photoURL??void 0,u=t.tenantId??void 0,c=t._redirectEventId??void 0,l=t.createdAt??void 0,d=t.lastLoginAt??void 0,{uid:g,emailVerified:y,isAnonymous:R,providerData:C,stsTokenManager:M}=t;ee(g&&M,e,"internal-error");const q=us.fromJSON(this.name,M);ee(typeof g=="string",e,"internal-error"),Nn(n,e.name),Nn(s,e.name),ee(typeof y=="boolean",e,"internal-error"),ee(typeof R=="boolean",e,"internal-error"),Nn(i,e.name),Nn(o,e.name),Nn(u,e.name),Nn(c,e.name),Nn(l,e.name),Nn(d,e.name);const Q=new Vt({uid:g,auth:e,email:s,emailVerified:y,displayName:n,isAnonymous:R,photoURL:o,phoneNumber:i,tenantId:u,stsTokenManager:q,createdAt:l,lastLoginAt:d});return C&&Array.isArray(C)&&(Q.providerData=C.map(te=>({...te}))),c&&(Q._redirectEventId=c),Q}static async _fromIdTokenResponse(e,t,n=!1){const s=new us;s.updateFromServerResponse(t);const i=new Vt({uid:t.localId,auth:e,stsTokenManager:s,isAnonymous:n});return await Wa(i),i}static async _fromGetAccountInfoResponse(e,t,n){const s=t.users[0];ee(s.localId!==void 0,"internal-error");const i=s.providerUserInfo!==void 0?R0(s.providerUserInfo):[],o=!(s.email&&s.passwordHash)&&!(i!=null&&i.length),u=new us;u.updateFromIdToken(n);const c=new Vt({uid:s.localId,auth:e,stsTokenManager:u,isAnonymous:o}),l={uid:s.localId,displayName:s.displayName||null,photoURL:s.photoUrl||null,email:s.email||null,emailVerified:s.emailVerified||!1,phoneNumber:s.phoneNumber||null,tenantId:s.tenantId||null,providerData:i,metadata:new u1(s.createdAt,s.lastLoginAt),isAnonymous:!(s.email&&s.passwordHash)&&!(i!=null&&i.length)};return Object.assign(c,l),c}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Qf=new Map;function sn(r){mn(r instanceof Function,"Expected a class definition");let e=Qf.get(r);return e?(mn(e instanceof r,"Instance stored in cache mismatched with class"),e):(e=new r,Qf.set(r,e),e)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class S0{constructor(){this.type="NONE",this.storage={}}async _isAvailable(){return!0}async _set(e,t){this.storage[e]=t}async _get(e){const t=this.storage[e];return t===void 0?null:t}async _remove(e){delete this.storage[e]}_addListener(e,t){}_removeListener(e,t){}}S0.type="NONE";const Yf=S0;/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Ea(r,e,t){return`firebase:${r}:${e}:${t}`}class cs{constructor(e,t,n){this.persistence=e,this.auth=t,this.userKey=n;const{config:s,name:i}=this.auth;this.fullUserKey=Ea(this.userKey,s.apiKey,i),this.fullPersistenceKey=Ea("persistence",s.apiKey,i),this.boundEventHandler=t._onStorageEvent.bind(t),this.persistence._addListener(this.fullUserKey,this.boundEventHandler)}setCurrentUser(e){return this.persistence._set(this.fullUserKey,e.toJSON())}async getCurrentUser(){const e=await this.persistence._get(this.fullUserKey);if(!e)return null;if(typeof e=="string"){const t=await Ka(this.auth,{idToken:e}).catch(()=>{});return t?Vt._fromGetAccountInfoResponse(this.auth,t,e):null}return Vt._fromJSON(this.auth,e)}removeCurrentUser(){return this.persistence._remove(this.fullUserKey)}savePersistenceForRedirect(){return this.persistence._set(this.fullPersistenceKey,this.persistence.type)}async setPersistence(e){if(this.persistence===e)return;const t=await this.getCurrentUser();if(await this.removeCurrentUser(),this.persistence=e,t)return this.setCurrentUser(t)}delete(){this.persistence._removeListener(this.fullUserKey,this.boundEventHandler)}static async create(e,t,n="authUser"){if(!t.length)return new cs(sn(Yf),e,n);const s=(await Promise.all(t.map(async l=>{if(await l._isAvailable())return l}))).filter(l=>l);let i=s[0]||sn(Yf);const o=Ea(n,e.config.apiKey,e.name);let u=null;for(const l of t)try{const d=await l._get(o);if(d){let g;if(typeof d=="string"){const y=await Ka(e,{idToken:d}).catch(()=>{});if(!y)break;g=await Vt._fromGetAccountInfoResponse(e,y,d)}else g=Vt._fromJSON(e,d);l!==i&&(u=g),i=l;break}}catch{}const c=s.filter(l=>l._shouldAllowMigration);return!i._shouldAllowMigration||!c.length?new cs(i,e,n):(i=c[0],u&&await i._set(o,u.toJSON()),await Promise.all(t.map(async l=>{if(l!==i)try{await l._remove(o)}catch{}})),new cs(i,e,n))}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Xf(r){const e=r.toLowerCase();if(e.includes("opera/")||e.includes("opr/")||e.includes("opios/"))return"Opera";if(N0(e))return"IEMobile";if(e.includes("msie")||e.includes("trident/"))return"IE";if(e.includes("edge/"))return"Edge";if(P0(e))return"Firefox";if(e.includes("silk/"))return"Silk";if(x0(e))return"Blackberry";if(D0(e))return"Webos";if(b0(e))return"Safari";if((e.includes("chrome/")||C0(e))&&!e.includes("edge/"))return"Chrome";if(V0(e))return"Android";{const t=/([a-zA-Z\d\.]+)\/[a-zA-Z\d\.]*$/,n=r.match(t);if((n==null?void 0:n.length)===2)return n[1]}return"Other"}function P0(r=qe()){return/firefox\//i.test(r)}function b0(r=qe()){const e=r.toLowerCase();return e.includes("safari/")&&!e.includes("chrome/")&&!e.includes("crios/")&&!e.includes("android")}function C0(r=qe()){return/crios\//i.test(r)}function N0(r=qe()){return/iemobile/i.test(r)}function V0(r=qe()){return/android/i.test(r)}function x0(r=qe()){return/blackberry/i.test(r)}function D0(r=qe()){return/webos/i.test(r)}function kl(r=qe()){return/iphone|ipad|ipod/i.test(r)||/macintosh/i.test(r)&&/mobile/i.test(r)}function k_(r=qe()){var e;return kl(r)&&!!((e=window.navigator)!=null&&e.standalone)}function L_(){return wg()&&document.documentMode===10}function O0(r=qe()){return kl(r)||V0(r)||D0(r)||x0(r)||/windows phone/i.test(r)||N0(r)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function k0(r,e=[]){let t;switch(r){case"Browser":t=Xf(qe());break;case"Worker":t=`${Xf(qe())}-${r}`;break;default:t=r}const n=e.length?e.join(","):"FirebaseCore-web";return`${t}/JsCore/${ks}/${n}`}/**
 * @license
 * Copyright 2022 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class M_{constructor(e){this.auth=e,this.queue=[]}pushCallback(e,t){const n=i=>new Promise((o,u)=>{try{const c=e(i);o(c)}catch(c){u(c)}});n.onAbort=t,this.queue.push(n);const s=this.queue.length-1;return()=>{this.queue[s]=()=>Promise.resolve()}}async runMiddleware(e){if(this.auth.currentUser===e)return;const t=[];try{for(const n of this.queue)await n(e),n.onAbort&&t.push(n.onAbort)}catch(n){t.reverse();for(const s of t)try{s()}catch{}throw this.auth._errorFactory.create("login-blocked",{originalMessage:n==null?void 0:n.message})}}}/**
 * @license
 * Copyright 2023 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function F_(r,e={}){return Qs(r,"GET","/v2/passwordPolicy",Dl(r,e))}/**
 * @license
 * Copyright 2023 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const U_=6;class B_{constructor(e){var n;const t=e.customStrengthOptions;this.customStrengthOptions={},this.customStrengthOptions.minPasswordLength=t.minPasswordLength??U_,t.maxPasswordLength&&(this.customStrengthOptions.maxPasswordLength=t.maxPasswordLength),t.containsLowercaseCharacter!==void 0&&(this.customStrengthOptions.containsLowercaseLetter=t.containsLowercaseCharacter),t.containsUppercaseCharacter!==void 0&&(this.customStrengthOptions.containsUppercaseLetter=t.containsUppercaseCharacter),t.containsNumericCharacter!==void 0&&(this.customStrengthOptions.containsNumericCharacter=t.containsNumericCharacter),t.containsNonAlphanumericCharacter!==void 0&&(this.customStrengthOptions.containsNonAlphanumericCharacter=t.containsNonAlphanumericCharacter),this.enforcementState=e.enforcementState,this.enforcementState==="ENFORCEMENT_STATE_UNSPECIFIED"&&(this.enforcementState="OFF"),this.allowedNonAlphanumericCharacters=((n=e.allowedNonAlphanumericCharacters)==null?void 0:n.join(""))??"",this.forceUpgradeOnSignin=e.forceUpgradeOnSignin??!1,this.schemaVersion=e.schemaVersion}validatePassword(e){const t={isValid:!0,passwordPolicy:this};return this.validatePasswordLengthOptions(e,t),this.validatePasswordCharacterOptions(e,t),t.isValid&&(t.isValid=t.meetsMinPasswordLength??!0),t.isValid&&(t.isValid=t.meetsMaxPasswordLength??!0),t.isValid&&(t.isValid=t.containsLowercaseLetter??!0),t.isValid&&(t.isValid=t.containsUppercaseLetter??!0),t.isValid&&(t.isValid=t.containsNumericCharacter??!0),t.isValid&&(t.isValid=t.containsNonAlphanumericCharacter??!0),t}validatePasswordLengthOptions(e,t){const n=this.customStrengthOptions.minPasswordLength,s=this.customStrengthOptions.maxPasswordLength;n&&(t.meetsMinPasswordLength=e.length>=n),s&&(t.meetsMaxPasswordLength=e.length<=s)}validatePasswordCharacterOptions(e,t){this.updatePasswordCharacterOptionsStatuses(t,!1,!1,!1,!1);let n;for(let s=0;s<e.length;s++)n=e.charAt(s),this.updatePasswordCharacterOptionsStatuses(t,n>="a"&&n<="z",n>="A"&&n<="Z",n>="0"&&n<="9",this.allowedNonAlphanumericCharacters.includes(n))}updatePasswordCharacterOptionsStatuses(e,t,n,s,i){this.customStrengthOptions.containsLowercaseLetter&&(e.containsLowercaseLetter||(e.containsLowercaseLetter=t)),this.customStrengthOptions.containsUppercaseLetter&&(e.containsUppercaseLetter||(e.containsUppercaseLetter=n)),this.customStrengthOptions.containsNumericCharacter&&(e.containsNumericCharacter||(e.containsNumericCharacter=s)),this.customStrengthOptions.containsNonAlphanumericCharacter&&(e.containsNonAlphanumericCharacter||(e.containsNonAlphanumericCharacter=i))}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class q_{constructor(e,t,n,s){this.app=e,this.heartbeatServiceProvider=t,this.appCheckServiceProvider=n,this.config=s,this.currentUser=null,this.emulatorConfig=null,this.operations=Promise.resolve(),this.authStateSubscription=new Jf(this),this.idTokenSubscription=new Jf(this),this.beforeStateQueue=new M_(this),this.redirectUser=null,this.isProactiveRefreshEnabled=!1,this.EXPECTED_PASSWORD_POLICY_SCHEMA_VERSION=1,this._canInitEmulator=!0,this._isInitialized=!1,this._deleted=!1,this._initializationPromise=null,this._popupRedirectResolver=null,this._errorFactory=w0,this._agentRecaptchaConfig=null,this._tenantRecaptchaConfigs={},this._projectPasswordPolicy=null,this._tenantPasswordPolicies={},this._resolvePersistenceManagerAvailable=void 0,this.lastNotifiedUid=void 0,this.languageCode=null,this.tenantId=null,this.settings={appVerificationDisabledForTesting:!1},this.frameworks=[],this.name=e.name,this.clientVersion=s.sdkClientVersion,this._persistenceManagerAvailable=new Promise(i=>this._resolvePersistenceManagerAvailable=i)}_initializeWithPersistence(e,t){return t&&(this._popupRedirectResolver=sn(t)),this._initializationPromise=this.queue(async()=>{var n,s,i;if(!this._deleted&&(this.persistenceManager=await cs.create(this,e),(n=this._resolvePersistenceManagerAvailable)==null||n.call(this),!this._deleted)){if((s=this._popupRedirectResolver)!=null&&s._shouldInitProactively)try{await this._popupRedirectResolver._initialize(this)}catch{}await this.initializeCurrentUser(t),this.lastNotifiedUid=((i=this.currentUser)==null?void 0:i.uid)||null,!this._deleted&&(this._isInitialized=!0)}}),this._initializationPromise}async _onStorageEvent(){if(this._deleted)return;const e=await this.assertedPersistence.getCurrentUser();if(!(!this.currentUser&&!e)){if(this.currentUser&&e&&this.currentUser.uid===e.uid){this._currentUser._assign(e),await this.currentUser.getIdToken();return}await this._updateCurrentUser(e,!0)}}async initializeCurrentUserFromIdToken(e){try{const t=await Ka(this,{idToken:e}),n=await Vt._fromGetAccountInfoResponse(this,t,e);await this.directlySetCurrentUser(n)}catch(t){console.warn("FirebaseServerApp could not login user with provided authIdToken: ",t),await this.directlySetCurrentUser(null)}}async initializeCurrentUser(e){var i;if(Nt(this.app)){const o=this.app.settings.authIdToken;return o?new Promise(u=>{setTimeout(()=>this.initializeCurrentUserFromIdToken(o).then(u,u))}):this.directlySetCurrentUser(null)}const t=await this.assertedPersistence.getCurrentUser();let n=t,s=!1;if(e&&this.config.authDomain){await this.getOrInitRedirectPersistenceManager();const o=(i=this.redirectUser)==null?void 0:i._redirectEventId,u=n==null?void 0:n._redirectEventId,c=await this.tryRedirectSignIn(e);(!o||o===u)&&(c!=null&&c.user)&&(n=c.user,s=!0)}if(!n)return this.directlySetCurrentUser(null);if(!n._redirectEventId){if(s)try{await this.beforeStateQueue.runMiddleware(n)}catch(o){n=t,this._popupRedirectResolver._overrideRedirectResult(this,()=>Promise.reject(o))}return n?this.reloadAndSetCurrentUserOrClear(n):this.directlySetCurrentUser(null)}return ee(this._popupRedirectResolver,this,"argument-error"),await this.getOrInitRedirectPersistenceManager(),this.redirectUser&&this.redirectUser._redirectEventId===n._redirectEventId?this.directlySetCurrentUser(n):this.reloadAndSetCurrentUserOrClear(n)}async tryRedirectSignIn(e){let t=null;try{t=await this._popupRedirectResolver._completeRedirectFn(this,e,!0)}catch{await this._setRedirectUser(null)}return t}async reloadAndSetCurrentUserOrClear(e){try{await Wa(e)}catch(t){if((t==null?void 0:t.code)!=="auth/network-request-failed")return this.directlySetCurrentUser(null)}return this.directlySetCurrentUser(e)}useDeviceLanguage(){this.languageCode=w_()}async _delete(){this._deleted=!0}async updateCurrentUser(e){if(Nt(this.app))return Promise.reject(Cr(this));const t=e?be(e):null;return t&&ee(t.auth.config.apiKey===this.config.apiKey,this,"invalid-user-token"),this._updateCurrentUser(t&&t._clone(this))}async _updateCurrentUser(e,t=!1){if(!this._deleted)return e&&ee(this.tenantId===e.tenantId,this,"tenant-id-mismatch"),t||await this.beforeStateQueue.runMiddleware(e),this.queue(async()=>{await this.directlySetCurrentUser(e),this.notifyAuthListeners()})}async signOut(){return Nt(this.app)?Promise.reject(Cr(this)):(await this.beforeStateQueue.runMiddleware(null),(this.redirectPersistenceManager||this._popupRedirectResolver)&&await this._setRedirectUser(null),this._updateCurrentUser(null,!0))}setPersistence(e){return Nt(this.app)?Promise.reject(Cr(this)):this.queue(async()=>{await this.assertedPersistence.setPersistence(sn(e))})}_getRecaptchaConfig(){return this.tenantId==null?this._agentRecaptchaConfig:this._tenantRecaptchaConfigs[this.tenantId]}async validatePassword(e){this._getPasswordPolicyInternal()||await this._updatePasswordPolicy();const t=this._getPasswordPolicyInternal();return t.schemaVersion!==this.EXPECTED_PASSWORD_POLICY_SCHEMA_VERSION?Promise.reject(this._errorFactory.create("unsupported-password-policy-schema-version",{})):t.validatePassword(e)}_getPasswordPolicyInternal(){return this.tenantId===null?this._projectPasswordPolicy:this._tenantPasswordPolicies[this.tenantId]}async _updatePasswordPolicy(){const e=await F_(this),t=new B_(e);this.tenantId===null?this._projectPasswordPolicy=t:this._tenantPasswordPolicies[this.tenantId]=t}_getPersistenceType(){return this.assertedPersistence.persistence.type}_getPersistence(){return this.assertedPersistence.persistence}_updateErrorMap(e){this._errorFactory=new Br("auth","Firebase",e())}onAuthStateChanged(e,t,n){return this.registerStateListener(this.authStateSubscription,e,t,n)}beforeAuthStateChanged(e,t){return this.beforeStateQueue.pushCallback(e,t)}onIdTokenChanged(e,t,n){return this.registerStateListener(this.idTokenSubscription,e,t,n)}authStateReady(){return new Promise((e,t)=>{if(this.currentUser)e();else{const n=this.onAuthStateChanged(()=>{n(),e()},t)}})}async revokeAccessToken(e){if(this.currentUser){const t=await this.currentUser.getIdToken(),n={providerId:"apple.com",tokenType:"ACCESS_TOKEN",token:e,idToken:t};this.tenantId!=null&&(n.tenantId=this.tenantId),await O_(this,n)}}toJSON(){var e;return{apiKey:this.config.apiKey,authDomain:this.config.authDomain,appName:this.name,currentUser:(e=this._currentUser)==null?void 0:e.toJSON()}}async _setRedirectUser(e,t){const n=await this.getOrInitRedirectPersistenceManager(t);return e===null?n.removeCurrentUser():n.setCurrentUser(e)}async getOrInitRedirectPersistenceManager(e){if(!this.redirectPersistenceManager){const t=e&&sn(e)||this._popupRedirectResolver;ee(t,this,"argument-error"),this.redirectPersistenceManager=await cs.create(this,[sn(t._redirectPersistence)],"redirectUser"),this.redirectUser=await this.redirectPersistenceManager.getCurrentUser()}return this.redirectPersistenceManager}async _redirectUserForId(e){var t,n;return this._isInitialized&&await this.queue(async()=>{}),((t=this._currentUser)==null?void 0:t._redirectEventId)===e?this._currentUser:((n=this.redirectUser)==null?void 0:n._redirectEventId)===e?this.redirectUser:null}async _persistUserIfCurrent(e){if(e===this.currentUser)return this.queue(async()=>this.directlySetCurrentUser(e))}_notifyListenersIfCurrent(e){e===this.currentUser&&this.notifyAuthListeners()}_key(){return`${this.config.authDomain}:${this.config.apiKey}:${this.name}`}_startProactiveRefresh(){this.isProactiveRefreshEnabled=!0,this.currentUser&&this._currentUser._startProactiveRefresh()}_stopProactiveRefresh(){this.isProactiveRefreshEnabled=!1,this.currentUser&&this._currentUser._stopProactiveRefresh()}get _currentUser(){return this.currentUser}notifyAuthListeners(){var t;if(!this._isInitialized)return;this.idTokenSubscription.next(this.currentUser);const e=((t=this.currentUser)==null?void 0:t.uid)??null;this.lastNotifiedUid!==e&&(this.lastNotifiedUid=e,this.authStateSubscription.next(this.currentUser))}registerStateListener(e,t,n,s){if(this._deleted)return()=>{};const i=typeof t=="function"?t:t.next.bind(t);let o=!1;const u=this._isInitialized?Promise.resolve():this._initializationPromise;if(ee(u,this,"internal-error"),u.then(()=>{o||i(this.currentUser)}),typeof t=="function"){const c=e.addObserver(t,n,s);return()=>{o=!0,c()}}else{const c=e.addObserver(t);return()=>{o=!0,c()}}}async directlySetCurrentUser(e){this.currentUser&&this.currentUser!==e&&this._currentUser._stopProactiveRefresh(),e&&this.isProactiveRefreshEnabled&&e._startProactiveRefresh(),this.currentUser=e,e?await this.assertedPersistence.setCurrentUser(e):await this.assertedPersistence.removeCurrentUser()}queue(e){return this.operations=this.operations.then(e,e),this.operations}get assertedPersistence(){return ee(this.persistenceManager,this,"internal-error"),this.persistenceManager}_logFramework(e){!e||this.frameworks.includes(e)||(this.frameworks.push(e),this.frameworks.sort(),this.clientVersion=k0(this.config.clientPlatform,this._getFrameworks()))}_getFrameworks(){return this.frameworks}async _getAdditionalHeaders(){var s;const e={"X-Client-Version":this.clientVersion};this.app.options.appId&&(e["X-Firebase-gmpid"]=this.app.options.appId);const t=await((s=this.heartbeatServiceProvider.getImmediate({optional:!0}))==null?void 0:s.getHeartbeatsHeader());t&&(e["X-Firebase-Client"]=t);const n=await this._getAppCheckToken();return n&&(e["X-Firebase-AppCheck"]=n),e}async _getAppCheckToken(){var t;if(Nt(this.app)&&this.app.settings.appCheckToken)return this.app.settings.appCheckToken;const e=await((t=this.appCheckServiceProvider.getImmediate({optional:!0}))==null?void 0:t.getToken());return e!=null&&e.error&&__(`Error while retrieving App Check token: ${e.error}`),e==null?void 0:e.token}}function Cu(r){return be(r)}class Jf{constructor(e){this.auth=e,this.observer=null,this.addObserver=Pg(t=>this.observer=t)}get next(){return ee(this.observer,this.auth,"internal-error"),this.observer.next.bind(this.observer)}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */let Ll={async loadJS(){throw new Error("Unable to load external scripts")},recaptchaV2Script:"",recaptchaEnterpriseScript:"",gapiScript:""};function $_(r){Ll=r}function G_(r){return Ll.loadJS(r)}function j_(){return Ll.gapiScript}function z_(r){return`__${r}${Math.floor(Math.random()*1e6)}`}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function H_(r,e){const t=Os(r,"auth");if(t.isInitialized()){const s=t.getImmediate(),i=t.getOptions();if(Nr(i,e??{}))return s;Zt(s,"already-initialized")}return t.initialize({options:e})}function K_(r,e){const t=(e==null?void 0:e.persistence)||[],n=(Array.isArray(t)?t:[t]).map(sn);e!=null&&e.errorMap&&r._updateErrorMap(e.errorMap),r._initializeWithPersistence(n,e==null?void 0:e.popupRedirectResolver)}function W_(r,e,t){const n=Cu(r);ee(/^https?:\/\//.test(e),n,"invalid-emulator-scheme");const s=!1,i=L0(e),{host:o,port:u}=Q_(e),c=u===null?"":`:${u}`,l={url:`${i}//${o}${c}/`},d=Object.freeze({host:o,port:u,protocol:i.replace(":",""),options:Object.freeze({disableWarnings:s})});if(!n._canInitEmulator){ee(n.config.emulator&&n.emulatorConfig,n,"emulator-config-failed"),ee(Nr(l,n.config.emulator)&&Nr(d,n.emulatorConfig),n,"emulator-config-failed");return}n.config.emulator=l,n.emulatorConfig=d,n.settings.appVerificationDisabledForTesting=!0,ho(o)?k2(`${i}//${o}${c}`):Y_()}function L0(r){const e=r.indexOf(":");return e<0?"":r.substr(0,e+1)}function Q_(r){const e=L0(r),t=/(\/\/)?([^?#/]+)/.exec(r.substr(e.length));if(!t)return{host:"",port:null};const n=t[2].split("@").pop()||"",s=/^(\[[^\]]+\])(:|$)/.exec(n);if(s){const i=s[1];return{host:i,port:Zf(n.substr(i.length+1))}}else{const[i,o]=n.split(":");return{host:i,port:Zf(o)}}}function Zf(r){if(!r)return null;const e=Number(r);return isNaN(e)?null:e}function Y_(){function r(){const e=document.createElement("p"),t=e.style;e.innerText="Running in emulator mode. Do not use with production credentials.",t.position="fixed",t.width="100%",t.backgroundColor="#ffffff",t.border=".1em solid #000000",t.color="#b50000",t.bottom="0px",t.left="0px",t.margin="0px",t.zIndex="10000",t.textAlign="center",e.classList.add("firebase-emulator-warning"),document.body.appendChild(e)}typeof console<"u"&&typeof console.info=="function"&&console.info("WARNING: You are using the Auth Emulator, which is intended for local testing only.  Do not use with production credentials."),typeof window<"u"&&typeof document<"u"&&(document.readyState==="loading"?window.addEventListener("DOMContentLoaded",r):r())}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class M0{constructor(e,t){this.providerId=e,this.signInMethod=t}toJSON(){return rn("not implemented")}_getIdTokenResponse(e){return rn("not implemented")}_linkToIdToken(e,t){return rn("not implemented")}_getReauthenticationResolver(e){return rn("not implemented")}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function ls(r,e){return R_(r,"POST","/v1/accounts:signInWithIdp",Dl(r,e))}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const X_="http://localhost";class Mr extends M0{constructor(){super(...arguments),this.pendingToken=null}static _fromParams(e){const t=new Mr(e.providerId,e.signInMethod);return e.idToken||e.accessToken?(e.idToken&&(t.idToken=e.idToken),e.accessToken&&(t.accessToken=e.accessToken),e.nonce&&!e.pendingToken&&(t.nonce=e.nonce),e.pendingToken&&(t.pendingToken=e.pendingToken)):e.oauthToken&&e.oauthTokenSecret?(t.accessToken=e.oauthToken,t.secret=e.oauthTokenSecret):Zt("argument-error"),t}toJSON(){return{idToken:this.idToken,accessToken:this.accessToken,secret:this.secret,nonce:this.nonce,pendingToken:this.pendingToken,providerId:this.providerId,signInMethod:this.signInMethod}}static fromJSON(e){const t=typeof e=="string"?JSON.parse(e):e,{providerId:n,signInMethod:s,...i}=t;if(!n||!s)return null;const o=new Mr(n,s);return o.idToken=i.idToken||void 0,o.accessToken=i.accessToken||void 0,o.secret=i.secret,o.nonce=i.nonce,o.pendingToken=i.pendingToken||null,o}_getIdTokenResponse(e){const t=this.buildRequest();return ls(e,t)}_linkToIdToken(e,t){const n=this.buildRequest();return n.idToken=t,ls(e,n)}_getReauthenticationResolver(e){const t=this.buildRequest();return t.autoCreate=!1,ls(e,t)}buildRequest(){const e={requestUri:X_,returnSecureToken:!0};if(this.pendingToken)e.pendingToken=this.pendingToken;else{const t={};this.idToken&&(t.id_token=this.idToken),this.accessToken&&(t.access_token=this.accessToken),this.secret&&(t.oauth_token_secret=this.secret),t.providerId=this.providerId,this.nonce&&!this.pendingToken&&(t.nonce=this.nonce),e.postBody=lo(t)}return e}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Ml{constructor(e){this.providerId=e,this.defaultLanguageCode=null,this.customParameters={}}setDefaultLanguage(e){this.defaultLanguageCode=e}setCustomParameters(e){return this.customParameters=e,this}getCustomParameters(){return this.customParameters}}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Co extends Ml{constructor(){super(...arguments),this.scopes=[]}addScope(e){return this.scopes.includes(e)||this.scopes.push(e),this}getScopes(){return[...this.scopes]}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class On extends Co{constructor(){super("facebook.com")}static credential(e){return Mr._fromParams({providerId:On.PROVIDER_ID,signInMethod:On.FACEBOOK_SIGN_IN_METHOD,accessToken:e})}static credentialFromResult(e){return On.credentialFromTaggedObject(e)}static credentialFromError(e){return On.credentialFromTaggedObject(e.customData||{})}static credentialFromTaggedObject({_tokenResponse:e}){if(!e||!("oauthAccessToken"in e)||!e.oauthAccessToken)return null;try{return On.credential(e.oauthAccessToken)}catch{return null}}}On.FACEBOOK_SIGN_IN_METHOD="facebook.com";On.PROVIDER_ID="facebook.com";/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class kn extends Co{constructor(){super("google.com"),this.addScope("profile")}static credential(e,t){return Mr._fromParams({providerId:kn.PROVIDER_ID,signInMethod:kn.GOOGLE_SIGN_IN_METHOD,idToken:e,accessToken:t})}static credentialFromResult(e){return kn.credentialFromTaggedObject(e)}static credentialFromError(e){return kn.credentialFromTaggedObject(e.customData||{})}static credentialFromTaggedObject({_tokenResponse:e}){if(!e)return null;const{oauthIdToken:t,oauthAccessToken:n}=e;if(!t&&!n)return null;try{return kn.credential(t,n)}catch{return null}}}kn.GOOGLE_SIGN_IN_METHOD="google.com";kn.PROVIDER_ID="google.com";/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Ln extends Co{constructor(){super("github.com")}static credential(e){return Mr._fromParams({providerId:Ln.PROVIDER_ID,signInMethod:Ln.GITHUB_SIGN_IN_METHOD,accessToken:e})}static credentialFromResult(e){return Ln.credentialFromTaggedObject(e)}static credentialFromError(e){return Ln.credentialFromTaggedObject(e.customData||{})}static credentialFromTaggedObject({_tokenResponse:e}){if(!e||!("oauthAccessToken"in e)||!e.oauthAccessToken)return null;try{return Ln.credential(e.oauthAccessToken)}catch{return null}}}Ln.GITHUB_SIGN_IN_METHOD="github.com";Ln.PROVIDER_ID="github.com";/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Mn extends Co{constructor(){super("twitter.com")}static credential(e,t){return Mr._fromParams({providerId:Mn.PROVIDER_ID,signInMethod:Mn.TWITTER_SIGN_IN_METHOD,oauthToken:e,oauthTokenSecret:t})}static credentialFromResult(e){return Mn.credentialFromTaggedObject(e)}static credentialFromError(e){return Mn.credentialFromTaggedObject(e.customData||{})}static credentialFromTaggedObject({_tokenResponse:e}){if(!e)return null;const{oauthAccessToken:t,oauthTokenSecret:n}=e;if(!t||!n)return null;try{return Mn.credential(t,n)}catch{return null}}}Mn.TWITTER_SIGN_IN_METHOD="twitter.com";Mn.PROVIDER_ID="twitter.com";/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Vs{constructor(e){this.user=e.user,this.providerId=e.providerId,this._tokenResponse=e._tokenResponse,this.operationType=e.operationType}static async _fromIdTokenResponse(e,t,n,s=!1){const i=await Vt._fromIdTokenResponse(e,n,s),o=e2(n);return new Vs({user:i,providerId:o,_tokenResponse:n,operationType:t})}static async _forOperation(e,t,n){await e._updateTokensIfNecessary(n,!0);const s=e2(n);return new Vs({user:e,providerId:s,_tokenResponse:n,operationType:t})}}function e2(r){return r.providerId?r.providerId:"phoneNumber"in r?"phone":null}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Qa extends en{constructor(e,t,n,s){super(t.code,t.message),this.operationType=n,this.user=s,Object.setPrototypeOf(this,Qa.prototype),this.customData={appName:e.name,tenantId:e.tenantId??void 0,_serverResponse:t.customData._serverResponse,operationType:n}}static _fromErrorAndOperation(e,t,n,s){return new Qa(e,t,n,s)}}function F0(r,e,t,n){return(e==="reauthenticate"?t._getReauthenticationResolver(r):t._getIdTokenResponse(r)).catch(i=>{throw i.code==="auth/multi-factor-auth-required"?Qa._fromErrorAndOperation(r,i,e,n):i})}async function J_(r,e,t=!1){const n=await co(r,e._linkToIdToken(r.auth,await r.getIdToken()),t);return Vs._forOperation(r,"link",n)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function Z_(r,e,t=!1){const{auth:n}=r;if(Nt(n.app))return Promise.reject(Cr(n));const s="reauthenticate";try{const i=await co(r,F0(n,s,e,r),t);ee(i.idToken,n,"internal-error");const o=Ol(i.idToken);ee(o,n,"internal-error");const{sub:u}=o;return ee(r.uid===u,n,"user-mismatch"),Vs._forOperation(r,s,i)}catch(i){throw(i==null?void 0:i.code)==="auth/user-not-found"&&Zt(n,"user-mismatch"),i}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function ey(r,e,t=!1){if(Nt(r.app))return Promise.reject(Cr(r));const n="signIn",s=await F0(r,n,e),i=await Vs._fromIdTokenResponse(r,n,s);return t||await r._updateCurrentUser(i.user),i}function ty(r,e,t,n){return be(r).onIdTokenChanged(e,t,n)}function ny(r,e,t){return be(r).beforeAuthStateChanged(e,t)}function Ew(r,e,t,n){return be(r).onAuthStateChanged(e,t,n)}function Iw(r){return be(r).signOut()}const Ya="__sak";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class U0{constructor(e,t){this.storageRetriever=e,this.type=t}_isAvailable(){try{return this.storage?(this.storage.setItem(Ya,"1"),this.storage.removeItem(Ya),Promise.resolve(!0)):Promise.resolve(!1)}catch{return Promise.resolve(!1)}}_set(e,t){return this.storage.setItem(e,JSON.stringify(t)),Promise.resolve()}_get(e){const t=this.storage.getItem(e);return Promise.resolve(t?JSON.parse(t):null)}_remove(e){return this.storage.removeItem(e),Promise.resolve()}get storage(){return this.storageRetriever()}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const ry=1e3,sy=10;class B0 extends U0{constructor(){super(()=>window.localStorage,"LOCAL"),this.boundEventHandler=(e,t)=>this.onStorageEvent(e,t),this.listeners={},this.localCache={},this.pollTimer=null,this.fallbackToPolling=O0(),this._shouldAllowMigration=!0}forAllChangedKeys(e){for(const t of Object.keys(this.listeners)){const n=this.storage.getItem(t),s=this.localCache[t];n!==s&&e(t,s,n)}}onStorageEvent(e,t=!1){if(!e.key){this.forAllChangedKeys((o,u,c)=>{this.notifyListeners(o,c)});return}const n=e.key;t?this.detachListener():this.stopPolling();const s=()=>{const o=this.storage.getItem(n);!t&&this.localCache[n]===o||this.notifyListeners(n,o)},i=this.storage.getItem(n);L_()&&i!==e.newValue&&e.newValue!==e.oldValue?setTimeout(s,sy):s()}notifyListeners(e,t){this.localCache[e]=t;const n=this.listeners[e];if(n)for(const s of Array.from(n))s(t&&JSON.parse(t))}startPolling(){this.stopPolling(),this.pollTimer=setInterval(()=>{this.forAllChangedKeys((e,t,n)=>{this.onStorageEvent(new StorageEvent("storage",{key:e,oldValue:t,newValue:n}),!0)})},ry)}stopPolling(){this.pollTimer&&(clearInterval(this.pollTimer),this.pollTimer=null)}attachListener(){window.addEventListener("storage",this.boundEventHandler)}detachListener(){window.removeEventListener("storage",this.boundEventHandler)}_addListener(e,t){Object.keys(this.listeners).length===0&&(this.fallbackToPolling?this.startPolling():this.attachListener()),this.listeners[e]||(this.listeners[e]=new Set,this.localCache[e]=this.storage.getItem(e)),this.listeners[e].add(t)}_removeListener(e,t){this.listeners[e]&&(this.listeners[e].delete(t),this.listeners[e].size===0&&delete this.listeners[e]),Object.keys(this.listeners).length===0&&(this.detachListener(),this.stopPolling())}async _set(e,t){await super._set(e,t),this.localCache[e]=JSON.stringify(t)}async _get(e){const t=await super._get(e);return this.localCache[e]=JSON.stringify(t),t}async _remove(e){await super._remove(e),delete this.localCache[e]}}B0.type="LOCAL";const iy=B0;/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class q0 extends U0{constructor(){super(()=>window.sessionStorage,"SESSION")}_addListener(e,t){}_removeListener(e,t){}}q0.type="SESSION";const $0=q0;/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function oy(r){return Promise.all(r.map(async e=>{try{return{fulfilled:!0,value:await e}}catch(t){return{fulfilled:!1,reason:t}}}))}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Nu{constructor(e){this.eventTarget=e,this.handlersMap={},this.boundEventHandler=this.handleEvent.bind(this)}static _getInstance(e){const t=this.receivers.find(s=>s.isListeningto(e));if(t)return t;const n=new Nu(e);return this.receivers.push(n),n}isListeningto(e){return this.eventTarget===e}async handleEvent(e){const t=e,{eventId:n,eventType:s,data:i}=t.data,o=this.handlersMap[s];if(!(o!=null&&o.size))return;t.ports[0].postMessage({status:"ack",eventId:n,eventType:s});const u=Array.from(o).map(async l=>l(t.origin,i)),c=await oy(u);t.ports[0].postMessage({status:"done",eventId:n,eventType:s,response:c})}_subscribe(e,t){Object.keys(this.handlersMap).length===0&&this.eventTarget.addEventListener("message",this.boundEventHandler),this.handlersMap[e]||(this.handlersMap[e]=new Set),this.handlersMap[e].add(t)}_unsubscribe(e,t){this.handlersMap[e]&&t&&this.handlersMap[e].delete(t),(!t||this.handlersMap[e].size===0)&&delete this.handlersMap[e],Object.keys(this.handlersMap).length===0&&this.eventTarget.removeEventListener("message",this.boundEventHandler)}}Nu.receivers=[];/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Fl(r="",e=10){let t="";for(let n=0;n<e;n++)t+=Math.floor(Math.random()*10);return r+t}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class ay{constructor(e){this.target=e,this.handlers=new Set}removeMessageHandler(e){e.messageChannel&&(e.messageChannel.port1.removeEventListener("message",e.onMessage),e.messageChannel.port1.close()),this.handlers.delete(e)}async _send(e,t,n=50){const s=typeof MessageChannel<"u"?new MessageChannel:null;if(!s)throw new Error("connection_unavailable");let i,o;return new Promise((u,c)=>{const l=Fl("",20);s.port1.start();const d=setTimeout(()=>{c(new Error("unsupported_event"))},n);o={messageChannel:s,onMessage(g){const y=g;if(y.data.eventId===l)switch(y.data.status){case"ack":clearTimeout(d),i=setTimeout(()=>{c(new Error("timeout"))},3e3);break;case"done":clearTimeout(i),u(y.data.response);break;default:clearTimeout(d),clearTimeout(i),c(new Error("invalid_response"));break}}},this.handlers.add(o),s.port1.addEventListener("message",o.onMessage),this.target.postMessage({eventType:e,eventId:l,data:t},[s.port2])}).finally(()=>{o&&this.removeMessageHandler(o)})}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Qt(){return window}function uy(r){Qt().location.href=r}/**
 * @license
 * Copyright 2020 Google LLC.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function G0(){return typeof Qt().WorkerGlobalScope<"u"&&typeof Qt().importScripts=="function"}async function cy(){if(!(navigator!=null&&navigator.serviceWorker))return null;try{return(await navigator.serviceWorker.ready).active}catch{return null}}function ly(){var r;return((r=navigator==null?void 0:navigator.serviceWorker)==null?void 0:r.controller)||null}function hy(){return G0()?self:null}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const j0="firebaseLocalStorageDb",dy=1,Xa="firebaseLocalStorage",z0="fbase_key";class No{constructor(e){this.request=e}toPromise(){return new Promise((e,t)=>{this.request.addEventListener("success",()=>{e(this.request.result)}),this.request.addEventListener("error",()=>{t(this.request.error)})})}}function Vu(r,e){return r.transaction([Xa],e?"readwrite":"readonly").objectStore(Xa)}function fy(){const r=indexedDB.deleteDatabase(j0);return new No(r).toPromise()}function H0(){const r=indexedDB.open(j0,dy);return new Promise((e,t)=>{r.addEventListener("error",()=>{t(r.error)}),r.addEventListener("upgradeneeded",()=>{const n=r.result;try{n.createObjectStore(Xa,{keyPath:z0})}catch(s){t(s)}}),r.addEventListener("success",async()=>{const n=r.result;n.objectStoreNames.contains(Xa)?e(n):(n.close(),await fy(),e(await H0()))})})}async function t2(r,e,t){const n=Vu(r,!0).put({[z0]:e,value:t});return new No(n).toPromise()}async function py(r,e){const t=Vu(r,!1).get(e),n=await new No(t).toPromise();return n===void 0?null:n.value}function n2(r,e){const t=Vu(r,!0).delete(e);return new No(t).toPromise()}const gy=800,my=3;class K0{constructor(){this.type="LOCAL",this.dbPromise=null,this._shouldAllowMigration=!0,this.listeners={},this.localCache={},this.pollTimer=null,this.pendingWrites=0,this.receiver=null,this.sender=null,this.serviceWorkerReceiverAvailable=!1,this.activeServiceWorker=null,this._workerInitializationPromise=this.initializeServiceWorkerMessaging().then(()=>{},()=>{})}async _openDb(){return this.dbPromise?this.dbPromise:(this.dbPromise=H0(),this.dbPromise.catch(()=>{this.dbPromise=null}),this.dbPromise)}async _withRetries(e){let t=0;for(;;)try{const n=await this._openDb();return await e(n)}catch(n){if(t++>my)throw n;this.dbPromise&&((await this.dbPromise).close(),this.dbPromise=null)}}async initializeServiceWorkerMessaging(){return G0()?this.initializeReceiver():this.initializeSender()}async initializeReceiver(){this.receiver=Nu._getInstance(hy()),this.receiver._subscribe("keyChanged",async(e,t)=>({keyProcessed:(await this._poll()).includes(t.key)})),this.receiver._subscribe("ping",async(e,t)=>["keyChanged"])}async initializeSender(){var t,n;if(this.activeServiceWorker=await cy(),!this.activeServiceWorker)return;this.sender=new ay(this.activeServiceWorker);const e=await this.sender._send("ping",{},800);e&&(t=e[0])!=null&&t.fulfilled&&(n=e[0])!=null&&n.value.includes("keyChanged")&&(this.serviceWorkerReceiverAvailable=!0)}async notifyServiceWorker(e){if(!(!this.sender||!this.activeServiceWorker||ly()!==this.activeServiceWorker))try{await this.sender._send("keyChanged",{key:e},this.serviceWorkerReceiverAvailable?800:50)}catch{}}async _isAvailable(){try{return indexedDB?(await this._withRetries(async e=>{await t2(e,Ya,"1"),await n2(e,Ya)}),!0):!1}catch{}return!1}async _withPendingWrite(e){this.pendingWrites++;try{await e()}finally{this.pendingWrites--}}async _set(e,t){return this._withPendingWrite(async()=>(await this._withRetries(n=>t2(n,e,t)),this.localCache[e]=t,this.notifyServiceWorker(e)))}async _get(e){const t=await this._withRetries(n=>py(n,e));return this.localCache[e]=t,t}async _remove(e){return this._withPendingWrite(async()=>(await this._withRetries(t=>n2(t,e)),delete this.localCache[e],this.notifyServiceWorker(e)))}async _poll(){const e=await this._withRetries(s=>{const i=Vu(s,!1).getAll();return new No(i).toPromise()});if(!e)return[];if(this.pendingWrites!==0)return[];const t=[],n=new Set;if(e.length!==0)for(const{fbase_key:s,value:i}of e)n.add(s),JSON.stringify(this.localCache[s])!==JSON.stringify(i)&&(this.notifyListeners(s,i),t.push(s));for(const s of Object.keys(this.localCache))this.localCache[s]&&!n.has(s)&&(this.notifyListeners(s,null),t.push(s));return t}notifyListeners(e,t){this.localCache[e]=t;const n=this.listeners[e];if(n)for(const s of Array.from(n))s(t)}startPolling(){this.stopPolling(),this.pollTimer=setInterval(async()=>this._poll(),gy)}stopPolling(){this.pollTimer&&(clearInterval(this.pollTimer),this.pollTimer=null)}_addListener(e,t){Object.keys(this.listeners).length===0&&this.startPolling(),this.listeners[e]||(this.listeners[e]=new Set,this._get(e)),this.listeners[e].add(t)}_removeListener(e,t){this.listeners[e]&&(this.listeners[e].delete(t),this.listeners[e].size===0&&delete this.listeners[e]),Object.keys(this.listeners).length===0&&this.stopPolling()}}K0.type="LOCAL";const _y=K0;new bo(3e4,6e4);/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function W0(r,e){return e?sn(e):(ee(r._popupRedirectResolver,r,"argument-error"),r._popupRedirectResolver)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Ul extends M0{constructor(e){super("custom","custom"),this.params=e}_getIdTokenResponse(e){return ls(e,this._buildIdpRequest())}_linkToIdToken(e,t){return ls(e,this._buildIdpRequest(t))}_getReauthenticationResolver(e){return ls(e,this._buildIdpRequest())}_buildIdpRequest(e){const t={requestUri:this.params.requestUri,sessionId:this.params.sessionId,postBody:this.params.postBody,tenantId:this.params.tenantId,pendingToken:this.params.pendingToken,returnSecureToken:!0,returnIdpCredential:!0};return e&&(t.idToken=e),t}}function yy(r){return ey(r.auth,new Ul(r),r.bypassAuthState)}function Ey(r){const{auth:e,user:t}=r;return ee(t,e,"internal-error"),Z_(t,new Ul(r),r.bypassAuthState)}async function Iy(r){const{auth:e,user:t}=r;return ee(t,e,"internal-error"),J_(t,new Ul(r),r.bypassAuthState)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class Q0{constructor(e,t,n,s,i=!1){this.auth=e,this.resolver=n,this.user=s,this.bypassAuthState=i,this.pendingPromise=null,this.eventManager=null,this.filter=Array.isArray(t)?t:[t]}execute(){return new Promise(async(e,t)=>{this.pendingPromise={resolve:e,reject:t};try{this.eventManager=await this.resolver._initialize(this.auth),await this.onExecution(),this.eventManager.registerConsumer(this)}catch(n){this.reject(n)}})}async onAuthEvent(e){const{urlResponse:t,sessionId:n,postBody:s,tenantId:i,error:o,type:u}=e;if(o){this.reject(o);return}const c={auth:this.auth,requestUri:t,sessionId:n,tenantId:i||void 0,postBody:s||void 0,user:this.user,bypassAuthState:this.bypassAuthState};try{this.resolve(await this.getIdpTask(u)(c))}catch(l){this.reject(l)}}onError(e){this.reject(e)}getIdpTask(e){switch(e){case"signInViaPopup":case"signInViaRedirect":return yy;case"linkViaPopup":case"linkViaRedirect":return Iy;case"reauthViaPopup":case"reauthViaRedirect":return Ey;default:Zt(this.auth,"internal-error")}}resolve(e){mn(this.pendingPromise,"Pending promise was never set"),this.pendingPromise.resolve(e),this.unregisterAndCleanUp()}reject(e){mn(this.pendingPromise,"Pending promise was never set"),this.pendingPromise.reject(e),this.unregisterAndCleanUp()}unregisterAndCleanUp(){this.eventManager&&this.eventManager.unregisterConsumer(this),this.pendingPromise=null,this.cleanUp()}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const wy=new bo(2e3,1e4);async function ww(r,e,t){if(Nt(r.app))return Promise.reject(Dt(r,"operation-not-supported-in-this-environment"));const n=Cu(r);y_(r,e,Ml);const s=W0(n,t);return new Tr(n,"signInViaPopup",e,s).executeNotNull()}class Tr extends Q0{constructor(e,t,n,s,i){super(e,t,s,i),this.provider=n,this.authWindow=null,this.pollId=null,Tr.currentPopupAction&&Tr.currentPopupAction.cancel(),Tr.currentPopupAction=this}async executeNotNull(){const e=await this.execute();return ee(e,this.auth,"internal-error"),e}async onExecution(){mn(this.filter.length===1,"Popup operations only handle one event");const e=Fl();this.authWindow=await this.resolver._openPopup(this.auth,this.provider,this.filter[0],e),this.authWindow.associatedEvent=e,this.resolver._originValidation(this.auth).catch(t=>{this.reject(t)}),this.resolver._isIframeWebStorageSupported(this.auth,t=>{t||this.reject(Dt(this.auth,"web-storage-unsupported"))}),this.pollUserCancellation()}get eventId(){var e;return((e=this.authWindow)==null?void 0:e.associatedEvent)||null}cancel(){this.reject(Dt(this.auth,"cancelled-popup-request"))}cleanUp(){this.authWindow&&this.authWindow.close(),this.pollId&&window.clearTimeout(this.pollId),this.authWindow=null,this.pollId=null,Tr.currentPopupAction=null}pollUserCancellation(){const e=()=>{var t,n;if((n=(t=this.authWindow)==null?void 0:t.window)!=null&&n.closed){this.pollId=window.setTimeout(()=>{this.pollId=null,this.reject(Dt(this.auth,"popup-closed-by-user"))},8e3);return}this.pollId=window.setTimeout(e,wy.get())};e()}}Tr.currentPopupAction=null;/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Ty="pendingRedirect",Ia=new Map;class Ay extends Q0{constructor(e,t,n=!1){super(e,["signInViaRedirect","linkViaRedirect","reauthViaRedirect","unknown"],t,void 0,n),this.eventId=null}async execute(){let e=Ia.get(this.auth._key());if(!e){try{const n=await vy(this.resolver,this.auth)?await super.execute():null;e=()=>Promise.resolve(n)}catch(t){e=()=>Promise.reject(t)}Ia.set(this.auth._key(),e)}return this.bypassAuthState||Ia.set(this.auth._key(),()=>Promise.resolve(null)),e()}async onAuthEvent(e){if(e.type==="signInViaRedirect")return super.onAuthEvent(e);if(e.type==="unknown"){this.resolve(null);return}if(e.eventId){const t=await this.auth._redirectUserForId(e.eventId);if(t)return this.user=t,super.onAuthEvent(e);this.resolve(null)}}async onExecution(){}cleanUp(){}}async function vy(r,e){const t=Py(e),n=Sy(r);if(!await n._isAvailable())return!1;const s=await n._get(t)==="true";return await n._remove(t),s}function Ry(r,e){Ia.set(r._key(),e)}function Sy(r){return sn(r._redirectPersistence)}function Py(r){return Ea(Ty,r.config.apiKey,r.name)}async function by(r,e,t=!1){if(Nt(r.app))return Promise.reject(Cr(r));const n=Cu(r),s=W0(n,e),o=await new Ay(n,s,t).execute();return o&&!t&&(delete o.user._redirectEventId,await n._persistUserIfCurrent(o.user),await n._setRedirectUser(null,e)),o}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Cy=10*60*1e3;class Ny{constructor(e){this.auth=e,this.cachedEventUids=new Set,this.consumers=new Set,this.queuedRedirectEvent=null,this.hasHandledPotentialRedirect=!1,this.lastProcessedEventTime=Date.now()}registerConsumer(e){this.consumers.add(e),this.queuedRedirectEvent&&this.isEventForConsumer(this.queuedRedirectEvent,e)&&(this.sendToConsumer(this.queuedRedirectEvent,e),this.saveEventToCache(this.queuedRedirectEvent),this.queuedRedirectEvent=null)}unregisterConsumer(e){this.consumers.delete(e)}onEvent(e){if(this.hasEventBeenHandled(e))return!1;let t=!1;return this.consumers.forEach(n=>{this.isEventForConsumer(e,n)&&(t=!0,this.sendToConsumer(e,n),this.saveEventToCache(e))}),this.hasHandledPotentialRedirect||!Vy(e)||(this.hasHandledPotentialRedirect=!0,t||(this.queuedRedirectEvent=e,t=!0)),t}sendToConsumer(e,t){var n;if(e.error&&!Y0(e)){const s=((n=e.error.code)==null?void 0:n.split("auth/")[1])||"internal-error";t.onError(Dt(this.auth,s))}else t.onAuthEvent(e)}isEventForConsumer(e,t){const n=t.eventId===null||!!e.eventId&&e.eventId===t.eventId;return t.filter.includes(e.type)&&n}hasEventBeenHandled(e){return Date.now()-this.lastProcessedEventTime>=Cy&&this.cachedEventUids.clear(),this.cachedEventUids.has(r2(e))}saveEventToCache(e){this.cachedEventUids.add(r2(e)),this.lastProcessedEventTime=Date.now()}}function r2(r){return[r.type,r.eventId,r.sessionId,r.tenantId].filter(e=>e).join("-")}function Y0({type:r,error:e}){return r==="unknown"&&(e==null?void 0:e.code)==="auth/no-auth-event"}function Vy(r){switch(r.type){case"signInViaRedirect":case"linkViaRedirect":case"reauthViaRedirect":return!0;case"unknown":return Y0(r);default:return!1}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function xy(r,e={}){return Qs(r,"GET","/v1/projects",e)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Dy=/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/,Oy=/^https?/;async function ky(r){if(r.config.emulator)return;const{authorizedDomains:e}=await xy(r);for(const t of e)try{if(Ly(t))return}catch{}Zt(r,"unauthorized-domain")}function Ly(r){const e=a1(),{protocol:t,hostname:n}=new URL(e);if(r.startsWith("chrome-extension://")){const o=new URL(r);return o.hostname===""&&n===""?t==="chrome-extension:"&&r.replace("chrome-extension://","")===e.replace("chrome-extension://",""):t==="chrome-extension:"&&o.hostname===n}if(!Oy.test(t))return!1;if(Dy.test(r))return n===r;const s=r.replace(/\./g,"\\.");return new RegExp("^(.+\\."+s+"|"+s+")$","i").test(n)}/**
 * @license
 * Copyright 2020 Google LLC.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const My=new bo(3e4,6e4);function s2(){const r=Qt().___jsl;if(r!=null&&r.H){for(const e of Object.keys(r.H))if(r.H[e].r=r.H[e].r||[],r.H[e].L=r.H[e].L||[],r.H[e].r=[...r.H[e].L],r.CP)for(let t=0;t<r.CP.length;t++)r.CP[t]=null}}function Fy(r){return new Promise((e,t)=>{var s,i,o;function n(){s2(),gapi.load("gapi.iframes",{callback:()=>{e(gapi.iframes.getContext())},ontimeout:()=>{s2(),t(Dt(r,"network-request-failed"))},timeout:My.get()})}if((i=(s=Qt().gapi)==null?void 0:s.iframes)!=null&&i.Iframe)e(gapi.iframes.getContext());else if((o=Qt().gapi)!=null&&o.load)n();else{const u=z_("iframefcb");return Qt()[u]=()=>{gapi.load?n():t(Dt(r,"network-request-failed"))},G_(`${j_()}?onload=${u}`).catch(c=>t(c))}}).catch(e=>{throw wa=null,e})}let wa=null;function Uy(r){return wa=wa||Fy(r),wa}/**
 * @license
 * Copyright 2020 Google LLC.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const By=new bo(5e3,15e3),qy="__/auth/iframe",$y="emulator/auth/iframe",Gy={style:{position:"absolute",top:"-100px",width:"1px",height:"1px"},"aria-hidden":"true",tabindex:"-1"},jy=new Map([["identitytoolkit.googleapis.com","p"],["staging-identitytoolkit.sandbox.googleapis.com","s"],["test-identitytoolkit.sandbox.googleapis.com","t"]]);function zy(r){const e=r.config;ee(e.authDomain,r,"auth-domain-config-required");const t=e.emulator?xl(e,$y):`https://${r.config.authDomain}/${qy}`,n={apiKey:e.apiKey,appName:r.name,v:ks},s=jy.get(r.config.apiHost);s&&(n.eid=s);const i=r._getFrameworks();return i.length&&(n.fw=i.join(",")),`${t}?${lo(n).slice(1)}`}async function Hy(r){const e=await Uy(r),t=Qt().gapi;return ee(t,r,"internal-error"),e.open({where:document.body,url:zy(r),messageHandlersFilter:t.iframes.CROSS_ORIGIN_IFRAMES_FILTER,attributes:Gy,dontclear:!0},n=>new Promise(async(s,i)=>{await n.restyle({setHideOnLeave:!1});const o=Dt(r,"network-request-failed"),u=Qt().setTimeout(()=>{i(o)},By.get());function c(){Qt().clearTimeout(u),s(n)}n.ping(c).then(c,()=>{i(o)})}))}/**
 * @license
 * Copyright 2020 Google LLC.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Ky={location:"yes",resizable:"yes",statusbar:"yes",toolbar:"no"},Wy=500,Qy=600,Yy="_blank",Xy="http://localhost";class i2{constructor(e){this.window=e,this.associatedEvent=null}close(){if(this.window)try{this.window.close()}catch{}}}function Jy(r,e,t,n=Wy,s=Qy){const i=Math.max((window.screen.availHeight-s)/2,0).toString(),o=Math.max((window.screen.availWidth-n)/2,0).toString();let u="";const c={...Ky,width:n.toString(),height:s.toString(),top:i,left:o},l=qe().toLowerCase();t&&(u=C0(l)?Yy:t),P0(l)&&(e=e||Xy,c.scrollbars="yes");const d=Object.entries(c).reduce((y,[R,C])=>`${y}${R}=${C},`,"");if(k_(l)&&u!=="_self")return Zy(e||"",u),new i2(null);const g=window.open(e||"",u,d);ee(g,r,"popup-blocked");try{g.focus()}catch{}return new i2(g)}function Zy(r,e){const t=document.createElement("a");t.href=r,t.target=e;const n=document.createEvent("MouseEvent");n.initMouseEvent("click",!0,!0,window,1,0,0,0,0,!1,!1,!1,!1,1,null),t.dispatchEvent(n)}/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const eE="__/auth/handler",tE="emulator/auth/handler",nE=encodeURIComponent("fac");async function o2(r,e,t,n,s,i){ee(r.config.authDomain,r,"auth-domain-config-required"),ee(r.config.apiKey,r,"invalid-api-key");const o={apiKey:r.config.apiKey,appName:r.name,authType:t,redirectUrl:n,v:ks,eventId:s};if(e instanceof Ml){e.setDefaultLanguage(r.languageCode),o.providerId=e.providerId||"",Sg(e.getCustomParameters())||(o.customParameters=JSON.stringify(e.getCustomParameters()));for(const[d,g]of Object.entries({}))o[d]=g}if(e instanceof Co){const d=e.getScopes().filter(g=>g!=="");d.length>0&&(o.scopes=d.join(","))}r.tenantId&&(o.tid=r.tenantId);const u=o;for(const d of Object.keys(u))u[d]===void 0&&delete u[d];const c=await r._getAppCheckToken(),l=c?`#${nE}=${encodeURIComponent(c)}`:"";return`${rE(r)}?${lo(u).slice(1)}${l}`}function rE({config:r}){return r.emulator?xl(r,tE):`https://${r.authDomain}/${eE}`}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Ec="webStorageSupport";class sE{constructor(){this.eventManagers={},this.iframes={},this.originValidationPromises={},this._redirectPersistence=$0,this._completeRedirectFn=by,this._overrideRedirectResult=Ry}async _openPopup(e,t,n,s){var o;mn((o=this.eventManagers[e._key()])==null?void 0:o.manager,"_initialize() not called before _openPopup()");const i=await o2(e,t,n,a1(),s);return Jy(e,i,Fl())}async _openRedirect(e,t,n,s){await this._originValidation(e);const i=await o2(e,t,n,a1(),s);return uy(i),new Promise(()=>{})}_initialize(e){const t=e._key();if(this.eventManagers[t]){const{manager:s,promise:i}=this.eventManagers[t];return s?Promise.resolve(s):(mn(i,"If manager is not set, promise should be"),i)}const n=this.initAndGetManager(e);return this.eventManagers[t]={promise:n},n.catch(()=>{delete this.eventManagers[t]}),n}async initAndGetManager(e){const t=await Hy(e),n=new Ny(e);return t.register("authEvent",s=>(ee(s==null?void 0:s.authEvent,e,"invalid-auth-event"),{status:n.onEvent(s.authEvent)?"ACK":"ERROR"}),gapi.iframes.CROSS_ORIGIN_IFRAMES_FILTER),this.eventManagers[e._key()]={manager:n},this.iframes[e._key()]=t,n}_isIframeWebStorageSupported(e,t){this.iframes[e._key()].send(Ec,{type:Ec},s=>{var o;const i=(o=s==null?void 0:s[0])==null?void 0:o[Ec];i!==void 0&&t(!!i),Zt(e,"internal-error")},gapi.iframes.CROSS_ORIGIN_IFRAMES_FILTER)}_originValidation(e){const t=e._key();return this.originValidationPromises[t]||(this.originValidationPromises[t]=ky(e)),this.originValidationPromises[t]}get _shouldInitProactively(){return O0()||b0()||kl()}}const iE=sE;var a2="@firebase/auth",u2="1.13.3";/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class oE{constructor(e){this.auth=e,this.internalListeners=new Map}getUid(){var e;return this.assertAuthConfigured(),((e=this.auth.currentUser)==null?void 0:e.uid)||null}async getToken(e){return this.assertAuthConfigured(),await this.auth._initializationPromise,this.auth.currentUser?{accessToken:await this.auth.currentUser.getIdToken(e)}:null}addAuthTokenListener(e){if(this.assertAuthConfigured(),this.internalListeners.has(e))return;const t=this.auth.onIdTokenChanged(n=>{e((n==null?void 0:n.stsTokenManager.accessToken)||null)});this.internalListeners.set(e,t),this.updateProactiveRefresh()}removeAuthTokenListener(e){this.assertAuthConfigured();const t=this.internalListeners.get(e);t&&(this.internalListeners.delete(e),t(),this.updateProactiveRefresh())}assertAuthConfigured(){ee(this.auth._initializationPromise,"dependent-sdk-initialized-before-auth")}updateProactiveRefresh(){this.internalListeners.size>0?this.auth._startProactiveRefresh():this.auth._stopProactiveRefresh()}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function aE(r){switch(r){case"Node":return"node";case"ReactNative":return"rn";case"Worker":return"webworker";case"Cordova":return"cordova";case"WebExtension":return"web-extension";default:return}}function uE(r){Yt(new kt("auth",(e,{options:t})=>{const n=e.getProvider("app").getImmediate(),s=e.getProvider("heartbeat"),i=e.getProvider("app-check-internal"),{apiKey:o,authDomain:u}=n.options;ee(o&&!o.includes(":"),"invalid-api-key",{appName:n.name});const c={apiKey:o,authDomain:u,clientPlatform:r,apiHost:"identitytoolkit.googleapis.com",tokenApiHost:"securetoken.googleapis.com",apiScheme:"https",sdkClientVersion:k0(r)},l=new q_(n,s,i,c);return K_(l,t),l},"PUBLIC").setInstantiationMode("EXPLICIT").setInstanceCreatedCallback((e,t,n)=>{e.getProvider("auth-internal").initialize()})),Yt(new kt("auth-internal",e=>{const t=Cu(e.getProvider("auth").getImmediate());return(n=>new oE(n))(t)},"PRIVATE").setInstantiationMode("EXPLICIT")),Rt(a2,u2,aE(r)),Rt(a2,u2,"esm2020")}/**
 * @license
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const cE=5*60,lE=N2("authIdTokenMaxAge")||cE;let c2=null;const hE=r=>async e=>{const t=e&&await e.getIdTokenResult(),n=t&&(new Date().getTime()-Date.parse(t.issuedAtTime))/1e3;if(n&&n>lE)return;const s=t==null?void 0:t.token;c2!==s&&(c2=s,await fetch(r,{method:s?"POST":"DELETE",headers:s?{Authorization:`Bearer ${s}`}:{}}))};function Tw(r=F2()){const e=Os(r,"auth");if(e.isInitialized())return e.getImmediate();const t=H_(r,{popupRedirectResolver:iE,persistence:[_y,iy,$0]}),n=N2("authTokenSyncURL");if(n&&typeof isSecureContext=="boolean"&&isSecureContext){const i=new URL(n,location.origin);if(location.origin===i.origin){const o=hE(i.toString());ny(t,o,()=>o(t.currentUser)),ty(t,u=>o(u))}}const s=gg("auth");return s&&W_(t,`http://${s}`),t}function dE(){var r;return((r=document.getElementsByTagName("head"))==null?void 0:r[0])??document}$_({loadJS(r){return new Promise((e,t)=>{const n=document.createElement("script");n.setAttribute("src",r),n.onload=e,n.onerror=s=>{const i=Dt("internal-error");i.customData=s,t(i)},n.type="text/javascript",n.charset="UTF-8",dE().appendChild(n)})},gapiScript:"https://apis.google.com/js/api.js",recaptchaV2Script:"https://www.google.com/recaptcha/api.js",recaptchaEnterpriseScript:"https://www.google.com/recaptcha/enterprise.js?render="});uE("Browser");const X0="@firebase/installations",Bl="0.6.22";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const J0=1e4,Z0=`w:${Bl}`,e7="FIS_v2",fE="https://firebaseinstallations.googleapis.com/v1",pE=60*60*1e3,gE="installations",mE="Installations";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const _E={"missing-app-config-values":'Missing App configuration value: "{$valueName}"',"not-registered":"Firebase Installation is not registered.","installation-not-found":"Firebase Installation not found.","request-failed":'{$requestName} request failed with error "{$serverCode} {$serverStatus}: {$serverMessage}"',"app-offline":"Could not process request. Application offline.","delete-pending-registration":"Can't delete installation while there is a pending registration request."},Fr=new Br(gE,mE,_E);function t7(r){return r instanceof en&&r.code.includes("request-failed")}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function n7({projectId:r}){return`${fE}/projects/${r}/installations`}function r7(r){return{token:r.token,requestStatus:2,expiresIn:EE(r.expiresIn),creationTime:Date.now()}}async function s7(r,e){const n=(await e.json()).error;return Fr.create("request-failed",{requestName:r,serverCode:n.code,serverMessage:n.message,serverStatus:n.status})}function i7({apiKey:r}){return new Headers({"Content-Type":"application/json",Accept:"application/json","x-goog-api-key":r})}function yE(r,{refreshToken:e}){const t=i7(r);return t.append("Authorization",IE(e)),t}async function o7(r){const e=await r();return e.status>=500&&e.status<600?r():e}function EE(r){return Number(r.replace("s","000"))}function IE(r){return`${e7} ${r}`}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function wE({appConfig:r,heartbeatServiceProvider:e},{fid:t}){const n=n7(r),s=i7(r),i=e.getImmediate({optional:!0});if(i){const l=await i.getHeartbeatsHeader();l&&s.append("x-firebase-client",l)}const o={fid:t,authVersion:e7,appId:r.appId,sdkVersion:Z0},u={method:"POST",headers:s,body:JSON.stringify(o)},c=await o7(()=>fetch(n,u));if(c.ok){const l=await c.json();return{fid:l.fid||t,registrationStatus:2,refreshToken:l.refreshToken,authToken:r7(l.authToken)}}else throw await s7("Create Installation",c)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function a7(r){return new Promise(e=>{setTimeout(e,r)})}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function TE(r){return btoa(String.fromCharCode(...r)).replace(/\+/g,"-").replace(/\//g,"_")}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const AE=/^[cdef][\w-]{21}$/,c1="";function vE(){try{const r=new Uint8Array(17);(self.crypto||self.msCrypto).getRandomValues(r),r[0]=112+r[0]%16;const t=RE(r);return AE.test(t)?t:c1}catch{return c1}}function RE(r){return TE(r).substr(0,22)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Ys(r){return`${r.appName}!${r.appId}`}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const xs=new Map;function u7(r,e){const t=Ys(r);c7(t,e),bE(t,e)}function SE(r,e){l7();const t=Ys(r);let n=xs.get(t);n||(n=new Set,xs.set(t,n)),n.add(e)}function PE(r,e){const t=Ys(r),n=xs.get(t);n&&(n.delete(e),n.size===0&&xs.delete(t),h7())}function c7(r,e){const t=xs.get(r);if(t)for(const n of t)n(e)}function bE(r,e){const t=l7();t&&t.postMessage({key:r,fid:e}),h7()}let Ar=null;function l7(){return!Ar&&"BroadcastChannel"in self&&(Ar=new BroadcastChannel("[Firebase] FID Change"),Ar.onmessage=r=>{c7(r.data.key,r.data.fid)}),Ar}function h7(){xs.size===0&&Ar&&(Ar.close(),Ar=null)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const CE="firebase-installations-database",NE=1,Ur="firebase-installations-store";let Ic=null;function ql(){return Ic||(Ic=eu(CE,NE,{upgrade:(r,e)=>{switch(e){case 0:r.createObjectStore(Ur)}}})),Ic}async function Ja(r,e){const t=Ys(r),s=(await ql()).transaction(Ur,"readwrite"),i=s.objectStore(Ur),o=await i.get(t);return await i.put(e,t),await s.done,(!o||o.fid!==e.fid)&&u7(r,e.fid),e}async function d7(r){const e=Ys(r),n=(await ql()).transaction(Ur,"readwrite");await n.objectStore(Ur).delete(e),await n.done}async function xu(r,e){const t=Ys(r),s=(await ql()).transaction(Ur,"readwrite"),i=s.objectStore(Ur),o=await i.get(t),u=e(o);return u===void 0?await i.delete(t):await i.put(u,t),await s.done,u&&(!o||o.fid!==u.fid)&&u7(r,u.fid),u}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function $l(r){let e;const t=await xu(r.appConfig,n=>{const s=VE(n),i=xE(r,s);return e=i.registrationPromise,i.installationEntry});return t.fid===c1?{installationEntry:await e}:{installationEntry:t,registrationPromise:e}}function VE(r){const e=r||{fid:vE(),registrationStatus:0};return f7(e)}function xE(r,e){if(e.registrationStatus===0){if(!navigator.onLine){const s=Promise.reject(Fr.create("app-offline"));return{installationEntry:e,registrationPromise:s}}const t={fid:e.fid,registrationStatus:1,registrationTime:Date.now()},n=DE(r,t);return{installationEntry:t,registrationPromise:n}}else return e.registrationStatus===1?{installationEntry:e,registrationPromise:OE(r)}:{installationEntry:e}}async function DE(r,e){try{const t=await wE(r,e);return Ja(r.appConfig,t)}catch(t){throw t7(t)&&t.customData.serverCode===409?await d7(r.appConfig):await Ja(r.appConfig,{fid:e.fid,registrationStatus:0}),t}}async function OE(r){let e=await l2(r.appConfig);for(;e.registrationStatus===1;)await a7(100),e=await l2(r.appConfig);if(e.registrationStatus===0){const{installationEntry:t,registrationPromise:n}=await $l(r);return n||t}return e}function l2(r){return xu(r,e=>{if(!e)throw Fr.create("installation-not-found");return f7(e)})}function f7(r){return kE(r)?{fid:r.fid,registrationStatus:0}:r}function kE(r){return r.registrationStatus===1&&r.registrationTime+J0<Date.now()}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function LE({appConfig:r,heartbeatServiceProvider:e},t){const n=ME(r,t),s=yE(r,t),i=e.getImmediate({optional:!0});if(i){const l=await i.getHeartbeatsHeader();l&&s.append("x-firebase-client",l)}const o={installation:{sdkVersion:Z0,appId:r.appId}},u={method:"POST",headers:s,body:JSON.stringify(o)},c=await o7(()=>fetch(n,u));if(c.ok){const l=await c.json();return r7(l)}else throw await s7("Generate Auth Token",c)}function ME(r,{fid:e}){return`${n7(r)}/${e}/authTokens:generate`}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function Gl(r,e=!1){let t;const n=await xu(r.appConfig,i=>{if(!p7(i))throw Fr.create("not-registered");const o=i.authToken;if(!e&&BE(o))return i;if(o.requestStatus===1)return t=FE(r,e),i;{if(!navigator.onLine)throw Fr.create("app-offline");const u=$E(i);return t=UE(r,u),u}});return t?await t:n.authToken}async function FE(r,e){let t=await h2(r.appConfig);for(;t.authToken.requestStatus===1;)await a7(100),t=await h2(r.appConfig);const n=t.authToken;return n.requestStatus===0?Gl(r,e):n}function h2(r){return xu(r,e=>{if(!p7(e))throw Fr.create("not-registered");const t=e.authToken;return GE(t)?{...e,authToken:{requestStatus:0}}:e})}async function UE(r,e){try{const t=await LE(r,e),n={...e,authToken:t};return await Ja(r.appConfig,n),t}catch(t){if(t7(t)&&(t.customData.serverCode===401||t.customData.serverCode===404))await d7(r.appConfig);else{const n={...e,authToken:{requestStatus:0}};await Ja(r.appConfig,n)}throw t}}function p7(r){return r!==void 0&&r.registrationStatus===2}function BE(r){return r.requestStatus===2&&!qE(r)}function qE(r){const e=Date.now();return e<r.creationTime||r.creationTime+r.expiresIn<e+pE}function $E(r){const e={requestStatus:1,requestTime:Date.now()};return{...r,authToken:e}}function GE(r){return r.requestStatus===1&&r.requestTime+J0<Date.now()}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function jE(r){const e=r,{installationEntry:t,registrationPromise:n}=await $l(e);return n?n.catch(console.error):Gl(e).catch(console.error),t.fid}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function zE(r,e=!1){const t=r;return await HE(t),(await Gl(t,e)).token}async function HE(r){const{registrationPromise:e}=await $l(r);e&&await e}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function KE(r,e){const{appConfig:t}=r;return SE(t,e),()=>{PE(t,e)}}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function WE(r){if(!r||!r.options)throw wc("App Configuration");if(!r.name)throw wc("App Name");const e=["projectId","apiKey","appId"];for(const t of e)if(!r.options[t])throw wc(t);return{appName:r.name,projectId:r.options.projectId,apiKey:r.options.apiKey,appId:r.options.appId}}function wc(r){return Fr.create("missing-app-config-values",{valueName:r})}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const g7="installations",QE="installations-internal",YE=r=>{const e=r.getProvider("app").getImmediate(),t=WE(e),n=Os(e,"heartbeat");return{app:e,appConfig:t,heartbeatServiceProvider:n,_delete:()=>Promise.resolve()}},XE=r=>{const e=r.getProvider("app").getImmediate(),t=Os(e,g7).getImmediate();return{getId:()=>jE(t),getToken:s=>zE(t,s)}};function JE(){Yt(new kt(g7,YE,"PUBLIC")),Yt(new kt(QE,XE,"PRIVATE"))}JE();Rt(X0,Bl);Rt(X0,Bl,"esm2020");/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const ZE="/firebase-messaging-sw.js",eI="/firebase-cloud-messaging-push-scope",m7="BDOU99-h67HcA6JeFXHbSNMu7e2yNNu3RzoMj8TM4W88jITfq7ZmPvIM1Iv-4_l2LxQcYwhqby2xGpWwzjfAnG4",tI="https://fcmregistrations.googleapis.com/v1",_7="google.c.a.c_id",nI="google.c.a.c_l",rI="google.c.a.ts",sI="google.c.a.e",d2=1e4;var f2;(function(r){r[r.DATA_MESSAGE=1]="DATA_MESSAGE",r[r.DISPLAY_NOTIFICATION=3]="DISPLAY_NOTIFICATION"})(f2||(f2={}));/**
 * @license
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under the License
 * is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
 * or implied. See the License for the specific language governing permissions and limitations under
 * the License.
 */var Ds;(function(r){r.PUSH_RECEIVED="push-received",r.NOTIFICATION_CLICKED="notification-clicked",r.FID_REGISTERED="fid-registered"})(Ds||(Ds={}));/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Ct(r){const e=new Uint8Array(r);return btoa(String.fromCharCode(...e)).replace(/=/g,"").replace(/\+/g,"-").replace(/\//g,"_")}function y7(r){const e="=".repeat((4-r.length%4)%4),t=(r+e).replace(/\-/g,"+").replace(/_/g,"/"),n=atob(t),s=new Uint8Array(n.length);for(let i=0;i<n.length;++i)s[i]=n.charCodeAt(i);return s}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const Tc="fcm_token_details_db",iI=5,p2="fcm_token_object_Store";async function oI(r){if("databases"in indexedDB&&!(await indexedDB.databases()).map(i=>i.name).includes(Tc))return null;let e=null;return(await eu(Tc,iI,{upgrade:async(n,s,i,o)=>{if(s<2||!n.objectStoreNames.contains(p2))return;const u=o.objectStore(p2),c=await u.index("fcmSenderId").get(r);if(await u.clear(),!!c){if(s===2){const l=c;if(!l.auth||!l.p256dh||!l.endpoint)return;e={token:l.fcmToken,createTime:l.createTime??Date.now(),subscriptionOptions:{auth:l.auth,p256dh:l.p256dh,endpoint:l.endpoint,swScope:l.swScope,vapidKey:typeof l.vapidKey=="string"?l.vapidKey:Ct(l.vapidKey)}}}else if(s===3){const l=c;e={token:l.fcmToken,createTime:l.createTime,subscriptionOptions:{auth:Ct(l.auth),p256dh:Ct(l.p256dh),endpoint:l.endpoint,swScope:l.swScope,vapidKey:Ct(l.vapidKey)}}}else if(s===4){const l=c;e={token:l.fcmToken,createTime:l.createTime,subscriptionOptions:{auth:Ct(l.auth),p256dh:Ct(l.p256dh),endpoint:l.endpoint,swScope:l.swScope,vapidKey:Ct(l.vapidKey)}}}}}})).close(),await ia(Tc),await ia("fcm_vapid_details_db"),await ia("undefined"),aI(e)?e:null}function aI(r){if(!r||!r.subscriptionOptions)return!1;const{subscriptionOptions:e}=r;return typeof r.createTime=="number"&&r.createTime>0&&typeof r.token=="string"&&r.token.length>0&&typeof e.auth=="string"&&e.auth.length>0&&typeof e.p256dh=="string"&&e.p256dh.length>0&&typeof e.endpoint=="string"&&e.endpoint.length>0&&typeof e.swScope=="string"&&e.swScope.length>0&&typeof e.vapidKey=="string"&&e.vapidKey.length>0}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const uI={"missing-app-config-values":'Missing App configuration value: "{$valueName}"',"only-available-in-window":"This method is available in a Window context.","only-available-in-sw":"This method is available in a service worker context.","permission-default":"The notification permission was not granted and dismissed instead.","permission-blocked":"The notification permission was not granted and blocked instead.","unsupported-browser":"This browser doesn't support the API's required to use the Firebase SDK.","indexed-db-unsupported":"This browser doesn't support indexedDb.open() (ex. Safari iFrame, Firefox Private Browsing, etc)","failed-service-worker-registration":"We are unable to register the default service worker. {$browserErrorMessage}","token-subscribe-failed":"A problem occurred while subscribing the user to FCM: {$errorInfo}","token-subscribe-no-token":"FCM returned no token when subscribing the user to push.","fid-registration-failed":"A problem occurred while creating an FCM registration via FID: {$errorInfo}","fid-unregister-failed":"A problem occurred while unregistering the FCM registration via FID: {$errorInfo}","fid-registration-idb-schema-unavailable":"Unable to read or persist FID registration metadata because the messaging IndexedDB schema is unavailable (for example, the database could not be upgraded to the latest version).","token-unsubscribe-failed":"A problem occurred while unsubscribing the user from FCM: {$errorInfo}","token-update-failed":"A problem occurred while updating the user from FCM: {$errorInfo}","token-update-no-token":"FCM returned no token when updating the user to push.","use-sw-after-get-token":"The useServiceWorker() method may only be called once and must be called before calling getToken() to ensure your service worker is used.","invalid-sw-registration":"The input to useServiceWorker() must be a ServiceWorkerRegistration.","invalid-bg-handler":"The input to setBackgroundMessageHandler() must be a function.","invalid-vapid-key":"The public VAPID key must be a string.","use-vapid-key-after-get-token":"The usePublicVapidKey() method may only be called once and must be called before calling getToken() to ensure your VAPID key is used.","invalid-on-registered-handler":"No onRegistered callback handler was provided or registered. Implement onRegistered() before register()."},pe=new Br("messaging","Messaging",uI);/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const g2="firebase-messaging-database",m2=2,_n="firebase-messaging-store",Ot="firebase-messaging-fid-registration-store",cI={openDB:eu,deleteDB:ia};let _2=cI,Bi=null;function lI(r,e,t){switch(e){case 0:if(r.createObjectStore(_n),t===1)break;case 1:t===2&&r.createObjectStore(Ot)}}function y2(r){return{upgrade:(e,t)=>{lI(e,t,r)},blocked:()=>{},blocking:(e,t,n)=>{var s;Bi=null,(s=n.target)==null||s.close()},terminated:()=>{Bi=null}}}function Xs(){return Bi||(Bi=_2.openDB(g2,m2,y2(2)).catch(()=>_2.openDB(g2,m2-1,y2(1)))),Bi}function E7(r,e){return r.objectStoreNames.contains(e)}function jl(r){if(!E7(r,Ot))throw pe.create("fid-registration-idb-schema-unavailable")}async function I7(r){const e=Js(r),n=await(await Xs()).transaction(_n).objectStore(_n).get(e);if(n)return n;{const s=await oI(r.appConfig.senderId);if(s)return await zl(r,s),s}}async function zl(r,e){const t=Js(r),n=await Xs(),s=[_n],i=E7(n,Ot);i&&s.push(Ot);const o=n.transaction(s,"readwrite");return await o.objectStore(_n).put(e,t),i&&await o.objectStore(Ot).delete(t),await o.done,e}async function hI(r){const e=Js(r),n=(await Xs()).transaction(_n,"readwrite");await n.objectStore(_n).delete(e),await n.done}async function Hl(r){const e=Js(r),t=await Xs();return jl(t),await t.transaction(Ot).objectStore(Ot).get(e)}async function dI(r,e){const t=Js(r),n=await Xs();jl(n);const s=n.transaction([_n,Ot],"readwrite");return await s.objectStore(Ot).put(e,t),await s.objectStore(_n).delete(t),await s.done,e}async function fI(r){const e=Js(r),t=await Xs();jl(t);const n=t.transaction(Ot,"readwrite");await n.objectStore(Ot).delete(e),await n.done}function Js({appConfig:r}){return r.appId}const E2="@firebase/messaging",l1="0.13.0";/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const pI=3,gI=1e3;async function mI(r,e){const t=await xo(r),n=Kl(e,r.appConfig.appName,!1),s={method:"POST",headers:t,body:JSON.stringify(n)};let i;try{i=await(await fetch(Vo(r.appConfig),s)).json()}catch(o){throw pe.create("token-subscribe-failed",{errorInfo:o==null?void 0:o.toString()})}if(i.error){const o=i.error.message;throw pe.create("token-subscribe-failed",{errorInfo:o})}if(!i.token)throw pe.create("token-subscribe-no-token");return i.token}async function _I(r,e){var c;const t=await xo(r),n=Kl(e,r.appConfig.appName,!0),s={method:"POST",headers:t,body:JSON.stringify(n)};let i;try{i=await TI(()=>fetch(Vo(r.appConfig),s),pI,gI)}catch(l){throw pe.create("fid-registration-failed",{errorInfo:l==null?void 0:l.toString()})}if(i.ok)return{responseFid:await EI(i)};let o;try{o=await i.json()}catch{throw pe.create("fid-registration-failed",{errorInfo:i.statusText})}const u=((c=o.error)==null?void 0:c.message)??i.statusText;throw pe.create("fid-registration-failed",{errorInfo:u})}async function yI(r,e){var i;const n={method:"DELETE",headers:await xo(r)};let s;try{s=await fetch(`${Vo(r.appConfig)}/${e}`,n)}catch(o){throw pe.create("fid-unregister-failed",{errorInfo:o==null?void 0:o.toString()})}if(!s.ok)try{throw((i=(await s.json()).error)==null?void 0:i.message)??s.statusText}catch(o){throw pe.create("fid-unregister-failed",{errorInfo:typeof o=="string"&&o||s.statusText||(o==null?void 0:o.toString())})}}async function EI(r){const e=await r.text();if(!e.trim())throw pe.create("fid-registration-failed",{errorInfo:"CreateRegistration succeeded but response body is empty"});let t;try{t=JSON.parse(e)}catch{throw pe.create("fid-registration-failed",{errorInfo:"CreateRegistration succeeded but response body is not valid JSON"})}const n=t.name;if(typeof n!="string"||n.length===0)throw pe.create("fid-registration-failed",{errorInfo:"CreateRegistration succeeded but response did not include a non-empty name"});return II(n)}const I2="/registrations/";function II(r){const e=r.indexOf(I2);if(e!==-1){const t=r.slice(e+I2.length);if(t.length>0)return t}throw pe.create("fid-registration-failed",{errorInfo:"CreateRegistration succeeded but response name is not a valid registration resource name"})}async function wI(r,e){const t=await xo(r),n=Kl(e.subscriptionOptions,r.appConfig.appName,!1),s={method:"PATCH",headers:t,body:JSON.stringify(n)};let i;try{i=await(await fetch(`${Vo(r.appConfig)}/${e.token}`,s)).json()}catch(o){throw pe.create("token-update-failed",{errorInfo:o==null?void 0:o.toString()})}if(i.error){const o=i.error.message;throw pe.create("token-update-failed",{errorInfo:o})}if(!i.token)throw pe.create("token-update-no-token");return i.token}async function w7(r,e){const n={method:"DELETE",headers:await xo(r)};try{const i=await(await fetch(`${Vo(r.appConfig)}/${e}`,n)).json();if(i.error){const o=i.error.message;throw pe.create("token-unsubscribe-failed",{errorInfo:o})}}catch(s){throw pe.create("token-unsubscribe-failed",{errorInfo:s==null?void 0:s.toString()})}}async function TI(r,e,t){let n;for(let s=0;s<e;s++)try{return await r()}catch(i){if(n=i,s<e-1){const o=t*Math.pow(2,s);await new Promise(u=>setTimeout(u,o))}}throw n}function Vo({projectId:r}){return`${tI}/projects/${r}/registrations`}async function xo({appConfig:r,installations:e}){const t=await e.getToken();return new Headers({"Content-Type":"application/json",Accept:"application/json","x-goog-api-key":r.apiKey,"x-goog-firebase-installations-auth":`FIS ${t}`})}function AI(r,e){var t,n;try{if(/^[a-zA-Z][a-zA-Z\d+\-.]*:/.test(r))return new URL(r).host}catch{}try{if(typeof self<"u"&&((t=self.location)!=null&&t.href))return new URL(r,self.location.origin).host}catch{}return typeof self<"u"&&((n=self.location)!=null&&n.host)?self.location.host:e}function Kl({p256dh:r,auth:e,endpoint:t,vapidKey:n,swScope:s},i,o){const u={web:{origin:AI(s,i),endpoint:t,auth:e,p256dh:r}};return o&&(u.fcm_sdk_version=l1),n!==m7&&(u.web.applicationPubKey=n),u}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const vI=7*24*60*60*1e3;async function RI(r){const e=await NI(r.swRegistration,r.vapidKey),t={vapidKey:r.vapidKey,swScope:r.swRegistration.scope,endpoint:e.endpoint,auth:Ct(e.getKey("auth")),p256dh:Ct(e.getKey("p256dh"))},n=await I7(r.firebaseDependencies);if(n){if(VI(n.subscriptionOptions,t))return Date.now()>=n.createTime+vI?CI(r,{token:n.token,createTime:Date.now(),subscriptionOptions:t}):n.token;try{await w7(r.firebaseDependencies,n.token)}catch(s){console.warn(s)}return w2(r.firebaseDependencies,t)}else return w2(r.firebaseDependencies,t)}async function SI(r,e){await w7(r.firebaseDependencies,e.token),await hI(r.firebaseDependencies),await T7(r.firebaseDependencies)}async function PI(r){const e=await Hl(r.firebaseDependencies).catch(()=>{}),t=e==null?void 0:e.fid;t&&await yI(r.firebaseDependencies,t),await T7(r.firebaseDependencies),t&&DI(r,t)}async function bI(r){const e=await I7(r.firebaseDependencies);e?await SI(r,e):await PI(r);const t=await r.swRegistration.pushManager.getSubscription();return t?t.unsubscribe():!0}async function CI(r,e){try{const t=await wI(r.firebaseDependencies,e),n={...e,token:t,createTime:Date.now()};return await zl(r.firebaseDependencies,n),t}catch(t){throw t}}async function w2(r,e){const n={token:await mI(r,e),createTime:Date.now(),subscriptionOptions:e};return await zl(r,n),n.token}async function NI(r,e){const t=await r.pushManager.getSubscription();return t||r.pushManager.subscribe({userVisibleOnly:!0,applicationServerKey:y7(e)})}function VI(r,e){const t=e.vapidKey===r.vapidKey,n=e.endpoint===r.endpoint,s=e.auth===r.auth,i=e.p256dh===r.p256dh;return t&&n&&s&&i}async function T7(r){try{await fI(r)}catch{}}function xI(r,e){const t=r.onRegisteredHandler;t&&(typeof t=="function"?t(e):t.next(e))}function DI(r,e){const t=r.onUnregisteredHandler;t&&(typeof t=="function"?t(e):t.next(e))}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function A7(r){try{r.swRegistration=await navigator.serviceWorker.register(ZE,{scope:eI}),r.swRegistration.update().catch(()=>{}),await OI(r.swRegistration)}catch(e){throw pe.create("failed-service-worker-registration",{browserErrorMessage:e==null?void 0:e.message})}}async function OI(r){return new Promise((e,t)=>{const n=setTimeout(()=>t(new Error(`Service worker not registered after ${d2} ms`)),d2),s=r.installing||r.waiting;r.active?(clearTimeout(n),e()):s?s.onstatechange=i=>{var o;((o=i.target)==null?void 0:o.state)==="activated"&&(s.onstatechange=null,clearTimeout(n),e())}:(clearTimeout(n),t(new Error("No incoming service worker found.")))})}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function v7(r,e){if(!e&&!r.swRegistration&&await A7(r),!(!e&&r.swRegistration)){if(!(e instanceof ServiceWorkerRegistration))throw pe.create("invalid-sw-registration");r.swRegistration=e}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function R7(r,e){e?r.vapidKey=e:r.vapidKey||(r.vapidKey=m7)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const T2=3;async function kI(r,e){const t=await LI(r.swRegistration,r.vapidKey),n={vapidKey:r.vapidKey,swScope:r.swRegistration.scope,endpoint:t.endpoint,auth:Ct(t.getKey("auth")),p256dh:Ct(t.getKey("p256dh"))},s=r.firebaseDependencies.installations;for(let i=0;i<T2;i++){const{responseFid:o}=await _I(r.firebaseDependencies,n);if(o===e)return;i<T2-1&&await s.getToken(!0)}throw pe.create("fid-registration-failed",{errorInfo:"CreateRegistration response FID does not match Firebase Installation ID"})}async function LI(r,e){const t=await r.pushManager.getSubscription();return t||r.pushManager.subscribe({userVisibleOnly:!0,applicationServerKey:y7(e)})}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const MI=7*24*60*60*1e3;async function S7(r,e){if(!navigator)throw pe.create("only-available-in-window");if(Notification.permission==="default"&&await Notification.requestPermission(),Notification.permission!=="granted")throw pe.create("permission-blocked");if(!r.onRegisteredHandler)throw pe.create("invalid-on-registered-handler");await R7(r,e==null?void 0:e.vapidKey),await v7(r,e==null?void 0:e.serviceWorkerRegistration);const t=r._registerNotifyChain.catch(()=>{});return r._registerNotifyChain=t.then(async()=>{const n=await r.firebaseDependencies.installations.getId(),s=await Hl(r.firebaseDependencies),i=Date.now();if((!s||s.fid!==n||i>=s.lastRegisterTime+MI)&&(await kI(r,n),await dI(r.firebaseDependencies,{fid:n,lastRegisterTime:i,vapidKey:r.vapidKey})),!r.onRegisteredHandler)throw pe.create("invalid-on-registered-handler");xI(r,n)}),r._registerNotifyChain}/**
 * @license
 * Copyright 2026 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function FI(r,e){return KE(e,()=>{(async()=>!r.onRegisteredHandler||!await Hl(r.firebaseDependencies)||await S7(r).catch(()=>{}))()})}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function A2(r){const e={from:r.from,collapseKey:r.collapse_key,messageId:r.fcmMessageId};return UI(e,r),BI(e,r),qI(e,r),e}function UI(r,e){if(!e.notification)return;r.notification={};const t=e.notification.title;t&&(r.notification.title=t);const n=e.notification.body;n&&(r.notification.body=n);const s=e.notification.image;s&&(r.notification.image=s);const i=e.notification.icon;i&&(r.notification.icon=i)}function BI(r,e){e.data&&(r.data=e.data)}function qI(r,e){var s,i,o,u;if(!e.fcmOptions&&!((s=e.notification)!=null&&s.click_action))return;r.fcmOptions={};const t=((i=e.fcmOptions)==null?void 0:i.link)??((o=e.notification)==null?void 0:o.click_action);t&&(r.fcmOptions.link=t);const n=(u=e.fcmOptions)==null?void 0:u.analytics_label;n&&(r.fcmOptions.analyticsLabel=n)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function $I(r){return typeof r=="object"&&!!r&&_7 in r}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function GI(r){if(!r||!r.options)throw Ac("App Configuration Object");if(!r.name)throw Ac("App Name");const e=["projectId","apiKey","appId","messagingSenderId"],{options:t}=r;for(const n of e)if(!t[n])throw Ac(n);return{appName:r.name,projectId:t.projectId,apiKey:t.apiKey,appId:t.appId,senderId:t.messagingSenderId}}function Ac(r){return pe.create("missing-app-config-values",{valueName:r})}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */class jI{constructor(e,t,n){this.deliveryMetricsExportedToBigQueryEnabled=!1,this.onBackgroundMessageHandler=null,this.onMessageHandler=null,this.onRegisteredHandler=null,this.onUnregisteredHandler=null,this._registerNotifyChain=Promise.resolve(),this._fidChangeUnsubscribe=null,this.logEvents=[],this.logQueue={state:"stopped"};const s=GI(e);this.firebaseDependencies={app:e,appConfig:s,installations:t,analyticsProvider:n}}_delete(){return this._fidChangeUnsubscribe&&(this._fidChangeUnsubscribe(),this._fidChangeUnsubscribe=null),this.logQueue.state==="scheduled"&&clearTimeout(this.logQueue.timerId),this.logQueue={state:"stopped"},Promise.resolve()}}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function P7(r,e){if(!navigator)throw pe.create("only-available-in-window");if(Notification.permission==="default"&&await Notification.requestPermission(),Notification.permission!=="granted")throw pe.create("permission-blocked");return await R7(r,e==null?void 0:e.vapidKey),await v7(r,e==null?void 0:e.serviceWorkerRegistration),RI(r)}/**
 * @license
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function zI(r,e,t){const n=HI(e);(await r.firebaseDependencies.analyticsProvider.get()).logEvent(n,{message_id:t[_7],message_name:t[nI],message_time:t[rI],message_device_time:Math.floor(Date.now()/1e3)})}function HI(r){switch(r){case Ds.NOTIFICATION_CLICKED:return"notification_open";case Ds.PUSH_RECEIVED:return"notification_foreground";default:throw new Error}}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function KI(r,e){const t=e.data;if(!t.isFirebaseMessaging)return;if(r.onMessageHandler&&t.messageType===Ds.PUSH_RECEIVED&&(typeof r.onMessageHandler=="function"?r.onMessageHandler(A2(t)):r.onMessageHandler.next(A2(t))),r.onRegisteredHandler&&t.messageType===Ds.FID_REGISTERED){const s=t.fid;typeof r.onRegisteredHandler=="function"?r.onRegisteredHandler(s):r.onRegisteredHandler.next(s)}const n=t.data;$I(n)&&n[sI]==="1"&&await zI(r,t.messageType,n)}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */const WI=r=>{const e=new jI(r.getProvider("app").getImmediate(),r.getProvider("installations-internal").getImmediate(),r.getProvider("analytics-internal"));return navigator.serviceWorker.addEventListener("message",t=>KI(e,t)),e._fidChangeUnsubscribe=FI(e,r.getProvider("installations").getImmediate()),e},QI=r=>{const e=r.getProvider("messaging").getImmediate();return{getToken:n=>P7(e,n),register:n=>S7(e,n)}};function YI(){Yt(new kt("messaging",WI,"PUBLIC")),Yt(new kt("messaging-internal",QI,"PRIVATE")),Rt(E2,l1),Rt(E2,l1,"esm2020")}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function XI(){try{await O2()}catch{return!1}return typeof window<"u"&&h1()&&Tg()&&"serviceWorker"in navigator&&"PushManager"in window&&"Notification"in window&&"fetch"in window&&ServiceWorkerRegistration.prototype.hasOwnProperty("showNotification")&&PushSubscription.prototype.hasOwnProperty("getKey")}/**
 * @license
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */async function JI(r){if(!navigator)throw pe.create("only-available-in-window");return r.swRegistration||await A7(r),bI(r)}/**
 * @license
 * Copyright 2017 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */function Aw(r=F2()){return XI().then(e=>{if(!e)throw pe.create("unsupported-browser")},e=>{throw pe.create("indexed-db-unsupported")}),Os(be(r),"messaging").getImmediate()}async function vw(r,e){return r=be(r),P7(r,e)}function Rw(r){return r=be(r),JI(r)}YI();export{nw as A,kn as G,sw as a,cw as b,tw as c,H3 as d,gw as e,dw as f,Tw as g,mw as h,Pm as i,XI as j,Aw as k,vw as l,Rw as m,hw as n,_w as o,uw as p,iw as q,lw as r,fw as s,Ew as t,pw as u,Iw as v,ow as w,ww as x,yw as y,aw as z};
