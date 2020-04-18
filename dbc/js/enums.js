// Enums are currently retrieved from TrinityCore repo, in a best case scenario these would come from DBD..
const reputationLevels = {
	0: 'None/Hated',
	1: 'Hostile',
	2: 'Unfriendly',
	3: 'Neutral',
	4: 'Friendly',
	5: 'Honored',
	6: 'Revered',
	7: 'Exalted',
}

const expansionLevels = {
	0: 'Vanilla',
	1: 'TBC',
	2: 'WotLK',
	3: 'Cata',
	4: 'MoP',
	5: 'WoD',
	6: 'Legion',
	7: 'BfA',
	8: 'Shadowlands',
}

const mapTypes = {
	0: 'Normal',
	1: 'Instance',
	2: 'Raid',
	3: 'BG',
	4: 'Arena',
	5: 'Scenario',
}

const itemBonusTypes = {
	0: 'Unk',
	1: 'ItemLevel',
	2: 'StatModifier',
	3: 'QualityModifier',
	4: 'TitleModifier',
	5: 'NameModifier',
	6: 'Socket',
	7: 'Appearance',
	8: 'RequiredLevel',
	9: 'DisplayToastMethod',
	10: 'RepairCostMultiplier',
	11: 'ScalingStatDistribution',
	12: 'DisenchantLootID',
	13: 'ScalingStatDistributionFixed',
	14: 'ItemLevelCanIncrease',
	15: 'RandomEnchantment',
	16: 'Bonding',
	17: 'RelicType',
	18: 'OverrideRequiredLevel',
	19: 'AzeriteTierUnlockSetID',
	20: 'Unk',
	21: 'CanDisenchant',
	22: 'CanScrap',
	23: 'ItemEffectID',
}

const criteriaTreeOperator = {
    0: 'SINGLE',
    1: 'SINGLE_NOT_COMPLETED',
    4: 'ALL',
    5: 'SUM_CHILDREN',
    6: 'MAX_CHILD',
    7: 'COUNT_DIRECT_CHILDREN',
    8: 'ANY',
    9: 'SUM_CHILDREN_WEIGHT'
}

const modifierTreeOperator = {
    2: 'SingleTrue',
    3: 'SingleFalse',
    4: 'All',
    8: 'Some'
};


const criteriaAdditionalCondition = {
	0: 'NONE',
	1: 'SOURCE_DRUNK_VALUE',
	2: 'SOURCE_PLAYER_CONDITION',
	3: 'ITEM_LEVEL',
	4: 'TARGET_CREATURE_ENTRY',
	5: 'TARGET_MUST_BE_PLAYER',
	6: 'TARGET_MUST_BE_DEAD',
	7: 'TARGET_MUST_BE_ENEMY',
	8: 'SOURCE_HAS_AURA',
	9: 'SOURCE_HAS_AURA_TYPE',
	10: 'TARGET_HAS_AURA',
	11: 'TARGET_HAS_AURA_TYPE',
	12: 'SOURCE_AURA_STATE',
	13: 'TARGET_AURA_STATE',
	14: 'ITEM_QUALITY_MIN',
	15: 'ITEM_QUALITY_EQUALS',
	16: 'SOURCE_IS_ALIVE',
	17: 'SOURCE_AREA_OR_ZONE',
	18: 'TARGET_AREA_OR_ZONE',
	19: 'UNK_19',
	20: 'MAP_DIFFICULTY_OLD',
	21: 'TARGET_CREATURE_YIELDS_XP',
	22: 'SOURCE_LEVEL_ABOVE_TARGET',
	23: 'SOURCE_LEVEL_EQUAL_TARGET',
	24: 'ARENA_TYPE',
	25: 'SOURCE_RACE',
	26: 'SOURCE_CLASS',
	27: 'TARGET_RACE',
	28: 'TARGET_CLASS',
	29: 'MAX_GROUP_MEMBERS',
	30: 'TARGET_CREATURE_TYPE',
	31: 'TARGET_CREATURE_FAMILY',
	32: 'SOURCE_MAP',
	33: 'CLIENT_VERSION',
	34: 'BATTLE_PET_TEAM_LEVEL',
	35: 'NOT_IN_GROUP',
	36: 'IN_GROUP',
	37: 'MIN_PERSONAL_RATING',
	38: 'TITLE_BIT_INDEX',
	39: 'SOURCE_LEVEL',
	40: 'TARGET_LEVEL',
	41: 'SOURCE_ZONE',
	42: 'TARGET_ZONE',
	43: 'SOURCE_HEALTH_PCT_LOWER',
	44: 'SOURCE_HEALTH_PCT_GREATER',
	45: 'SOURCE_HEALTH_PCT_EQUAL',
	46: 'TARGET_HEALTH_PCT_LOWER',
	47: 'TARGET_HEALTH_PCT_GREATER',
	48: 'TARGET_HEALTH_PCT_EQUAL',
	49: 'SOURCE_HEALTH_LOWER',
	50: 'SOURCE_HEALTH_GREATER',
	51: 'SOURCE_HEALTH_EQUAL',
	52: 'TARGET_HEALTH_LOWER',
	53: 'TARGET_HEALTH_GREATER',
	54: 'TARGET_HEALTH_EQUAL',
	55: 'TARGET_PLAYER_CONDITION',
	56: 'MIN_ACHIEVEMENT_POINTS',
	57: 'IN_LFG_DUNGEON',
	58: 'IN_LFG_RANDOM_DUNGEON',
	59: 'IN_LFG_FIRST_RANDOM_DUNGEON',
	60: 'UNK_60',
	61: 'REQUIRES_GUILD_GROUP',
	62: 'GUILD_REPUTATION',
	63: 'RATED_BATTLEGROUND',
	64: 'RATED_BATTLEGROUND_RATING',
	65: 'PROJECT_RARITY',
	66: 'PROJECT_RACE',
	67: 'WORLD_STATE_EXPRESSION',
	68: 'MAP_DIFFICULTY',
	69: 'SOURCE_LEVEL_GREATER',
	70: 'TARGET_LEVEL_GREATER',
	71: 'SOURCE_LEVEL_LOWER',
	72: 'TARGET_LEVEL_LOWER',
	73: 'MODIFIER_TREE',
	74: 'SCENARIO_ID',
	75: 'THE_TILLERS_REPUTATION',
	76: 'PET_BATTLE_ACHIEVEMENT_POINTS',
	77: 'UNK_77',
	78: 'BATTLE_PET_FAMILY',
	79: 'BATTLE_PET_HEALTH_PCT',
	80: 'GUILD_GROUP_MEMBERS',
	81: 'BATTLE_PET_ENTRY',
	82: 'SCENARIO_STEP_INDEX',
	83: 'CHALLENGE_MODE_MEDAL',
	84: 'IS_ON_QUEST',
	85: 'EXALTED_WITH_FACTION',
	86: 'HAS_ACHIEVEMENT',
	87: 'HAS_ACHIEVEMENT_ON_CHARACTER',
	88: 'CLOUD_SERPENT_REPUTATION',
	89: 'BATTLE_PET_BREED_QUALITY_ID',
	90: 'PET_BATTLE_IS_PVP',
	91: 'BATTLE_PET_SPECIES',
	92: 'ACTIVE_EXPANSION',
	93: 'UNK_93',
	94: 'FRIENDSHIP_REP_REACTION',
	95: 'FACTION_STANDING',
	96: 'ITEM_CLASS_AND_SUBCLASS',
	97: 'SOURCE_SEX',
	98: 'SOURCE_NATIVE_SEX',
	99: 'SKILL',
	100: 'UNK_100',
	101: 'NORMAL_PHASE_SHIFT',
	102: 'IN_PHASE',
	103: 'NOT_IN_PHASE',
	104: 'HAS_SPELL',
	105: 'ITEM_COUNT',
	106: 'ACCOUNT_EXPANSION',
	107: 'SOURCE_HAS_AURA_LABEL',
	108: 'UNK_108',
	109: 'TIME_IN_RANGE',
	110: 'REWARDED_QUEST',
	111: 'COMPLETED_QUEST',
	112: 'COMPLETED_QUEST_OBJECTIVE',
	113: 'EXPLORED_AREA',
	114: 'ITEM_COUNT_INCLUDING_BANK',
	115: 'UNK_115',
	116: 'SOURCE_PVP_FACTION_INDEX',
	117: 'LFG_VALUE_EQUAL',
	118: 'LFG_VALUE_GREATER',
	119: 'CURRENCY_AMOUNT',
	120: 'UNK_120',
	121: 'CURRENCY_TRACKED_AMOUNT',
	122: 'MAP_INSTANCE_TYPE',
	123: 'MENTOR',
	124: 'UNK_124',
	125: 'UNK_125',
	126: 'GARRISON_LEVEL_ABOVE',
	127: 'GARRISON_FOLLOWERS_ABOVE_LEVEL',
	128: 'GARRISON_FOLLOWERS_ABOVE_QUALITY',
	129: 'GARRISON_FOLLOWER_ABOVE_LEVEL_WITH_ABILITY',
	130: 'GARRISON_FOLLOWER_ABOVE_LEVEL_WITH_TRAIT',
	131: 'GARRISON_FOLLOWER_WITH_ABILITY_IN_BUILDING',
	132: 'GARRISON_FOLLOWER_WITH_TRAIT_IN_BUILDING',
	133: 'GARRISON_FOLLOWER_ABOVE_LEVEL_IN_BUILDING',
	134: 'GARRISON_BUILDING_ABOVE_LEVEL',
	135: 'GARRISON_BLUEPRINT',
	136: 'UNK_136',
	137: 'UNK_137',
	138: 'UNK_138',
	139: 'UNK_139',
	140: 'GARRISON_BUILDING_INACTIVE',
	141: 'UNK_141',
	142: 'GARRISON_BUILDING_EQUAL_LEVEL',
	143: 'GARRISON_FOLLOWER_WITH_ABILITY',
	144: 'GARRISON_FOLLOWER_WITH_TRAIT',
	145: 'GARRISON_FOLLOWER_ABOVE_QUALITY_WOD',
	146: 'GARRISON_FOLLOWER_EQUAL_LEVEL',
	147: 'GARRISON_RARE_MISSION',
	148: 'UNK_148',
	149: 'GARRISON_BUILDING_LEVEL',
	150: 'UNK_150',
	151: 'BATTLE_PET_SPECIES_IN_TEAM',
	152: 'BATTLE_PET_FAMILY_IN_TEAM',
	153: 'UNK_153',
	154: 'UNK_154',
	155: 'UNK_155',
	156: 'UNK_156',
	157: 'GARRISON_FOLLOWER_ID',
	158: 'QUEST_OBJECTIVE_PROGRESS_EQUAL',
	159: 'QUEST_OBJECTIVE_PROGRESS_ABOVE',
	160: 'UNK_160',
	161: 'UNK_161',
	162: 'UNK_162',
	163: 'UNK_163',
	164: 'UNK_164',
	165: 'UNK_165',
	166: 'UNK_166',
	167: 'GARRISON_MISSION_TYPE',
	168: 'GARRISON_FOLLOWER_ABOVE_ITEM_LEVEL',
	169: 'GARRISON_FOLLOWERS_ABOVE_ITEM_LEVEL',
	170: 'GARRISON_LEVEL_EQUAL',
	171: 'GARRISON_GROUP_SIZE',
	172: 'UNK_172',
	173: 'TARGETING_CORPSE',
	174: 'UNK_174',
	175: 'GARRISON_FOLLOWERS_LEVEL_EQUAL',
	176: 'GARRISON_FOLLOWER_ID_IN_BUILDING',
	177: 'UNK_177',
	178: 'UNK_178',
	179: 'WORLD_PVP_AREA',
	180: 'NON_OWN_GARRISON',
	181: 'UNK_181',
	183: 'UNK_182',
	183: 'UNK_183',
	184: 'GARRISON_FOLLOWERS_ITEM_LEVEL_ABOVE',
	185: 'UNK_185',
	186: 'UNK_186',
	187: 'GARRISON_FOLLOWER_TYPE',
	188: 'UNK_188',
	189: 'UNK_189',
	190: 'UNK_190',
	191: 'UNK_191',
	192: 'UNK_192',
	193: 'HONOR_LEVEL',
	194: 'PRESTIGE_LEVEL',
	195: 'UNK_195',
	196: 'UNK_196',
	197: 'UNK_197',
	198: 'UNK_198',
	199: 'UNK_198',
	200: 'ITEM_MODIFIED_APPEARANCE',
	201: 'GARRISON_SELECTED_TALENT',
	202: 'GARRISON_RESEARCHED_TALENT',
	203: 'HAS_CHARACTER_RESTRICTIONS',
	204: 'UNK_204',
	205: 'UNK_205',
	206: 'QUEST_INFO_ID',
	207: 'GARRISON_RESEARCHING_TALENT',
	208: 'ARTIFACT_APPEARANCE_SET_USED',
	209: 'CURRENCY_AMOUNT_EQUAL',
	210: 'UNK_210',
	211: 'SCENARIO_TYPE',
	212: 'ACCOUNT_EXPANSION_EQUAL',
	213: 'UNK_213',
	214: 'UNK_214',
	215: 'UNK_215',
	216: 'CHALLENGE_MODE_MEDAL_2',
	217: 'UNK_217',
	218: 'UNK_218',
	219: 'UNK_219',
	220: 'UNK_220',
	221: 'UNK_221',
	222: 'UNK_222',
	223: 'UNK_223',
	224: 'UNK_224',
	225: 'UNK_225',
	226: 'USED_LEVEL_BOOST',
	227: 'USED_RACE_CHANGE',
	228: 'USED_FACTION_CHANGE',
	229: 'UNK_229',
	230: 'UNK_230',
	231: 'ACHIEVEMENT_GLOBALLY_INCOMPLETED',
	232: 'MAIN_HAND_VISIBLE_SUBCLASS',
	233: 'OFF_HAND_VISIBLE_SUBCLASS',
	234: 'PVP_TIER',
	235: 'AZERITE_ITEM_LEVEL',
	236: 'UNK_236',
	237: 'UNK_237',
	238: 'UNK_238',
	239: 'PVP_TIER_GREATER',
	240: 'UNK_240',
	241: 'UNK_241',
	242: 'UNK_242',
	243: 'UNK_243',
	244: 'UNK_244',
	245: 'IN_WAR_MODE',
	246: 'UNK_246',
	247: 'KEYSTONE_LEVEL',
	248: 'UNK_248',
	249: 'KEYSTONE_DUNGEON',
	250: 'UNK_250',
	251: 'PVP_SEASON',
	252: 'SOURCE_DISPLAY_RACE',
	253: 'TARGET_DISPLAY_RACE',
	254: 'FRIENDSHIP_REP_REACTION_EXACT',
	255: 'SOURCE_AURA_COUNT_EQUAL',
	256: 'TARGET_AURA_COUNT_EQUAL',
	257: 'SOURCE_AURA_COUNT_GREATER',
	258: 'TARGET_AURA_COUNT_GREATER',
	259: 'UNLOCKED_AZERITE_ESSENCE_RANK_LOWER',
	260: 'UNLOCKED_AZERITE_ESSENCE_RANK_EQUAL',
	261: 'UNLOCKED_AZERITE_ESSENCE_RANK_GREATER',
	262: 'SOURCE_HAS_AURA_EFFECT_INDEX',
	263: 'SOURCE_SPECIALIZATION_ROLE',
	264: 'SOURCE_LEVEL_120',
	265: 'UNK_265',
	266: 'SELECTED_AZERITE_ESSENCE_RANK_LOWER',
	267: 'SELECTED_AZERITE_ESSENCE_RANK_GREATER',
	268: 'UNK_268',
	269: 'UNK_269',
	270: 'UNK_270',
	271: 'UNK_271',
	272: 'UNK_272',
	273: 'UNK_273',
	274: 'UNK_274',
	275: 'UNK_275',
	276: 'UNK_276',
	277: 'UNK_277',
	278: 'UNK_278',
	279: 'UNK_279',
	280: 'MAP_OR_COSMETIC_MAP',
	281: 'UNK_281',
	282: 'HAS_ENTITLEMENT',
	283: 'HAS_QUEST_SESSION',
	284: 'UNK_284',
	285: 'UNK_285'
};

const itemStatType = {
    0: 'MANA',
    1: 'HEALTH',
    3: 'AGILITY',
    4: 'STRENGTH',
    5: 'INTELLECT',
    6: 'SPIRIT',
    7: 'STAMINA',
    12: 'DEFENSE_SKILL_RATING',
    13: 'DODGE_RATING',
    14: 'PARRY_RATING',
    15: 'BLOCK_RATING',
    16: 'HIT_MELEE_RATING',
    17: 'HIT_RANGED_RATING',
    18: 'HIT_SPELL_RATING',
    19: 'CRIT_MELEE_RATING',
    20: 'CRIT_RANGED_RATING',
    21: 'CRIT_SPELL_RATING',
    22: 'CORRUPTION',
    23: 'CORRUPTION_RESISTANCE',
    24: 'HIT_TAKEN_SPELL_RATING',
    25: 'CRIT_TAKEN_MELEE_RATING',
    26: 'CRIT_TAKEN_RANGED_RATING',
    27: 'CRIT_TAKEN_SPELL_RATING',
    28: 'HASTE_MELEE_RATING',
    29: 'HASTE_RANGED_RATING',
    30: 'HASTE_SPELL_RATING',
    31: 'HIT_RATING',
    32: 'CRIT_RATING',
    33: 'HIT_TAKEN_RATING',
    34: 'CRIT_TAKEN_RATING',
    35: 'RESILIENCE_RATING',
    36: 'HASTE_RATING',
    37: 'EXPERTISE_RATING',
    38: 'ATTACK_POWER',
    39: 'RANGED_ATTACK_POWER',
    40: 'VERSATILITY',
    41: 'SPELL_HEALING_DONE',
    42: 'SPELL_DAMAGE_DONE',
    43: 'MANA_REGENERATION',
    44: 'ARMOR_PENETRATION_RATING',
    45: 'SPELL_POWER',
    46: 'HEALTH_REGEN',
    47: 'SPELL_PENETRATION',
    48: 'BLOCK_VALUE',
    49: 'MASTERY_RATING',
    50: 'EXTRA_ARMOR',
    51: 'FIRE_RESISTANCE',
    52: 'FROST_RESISTANCE',
    53: 'HOLY_RESISTANCE',
    54: 'SHADOW_RESISTANCE',
    55: 'NATURE_RESISTANCE',
    56: 'ARCANE_RESISTANCE',
    57: 'PVP_POWER',
    58: 'CR_AMPLIFY',
    59: 'CR_MULTISTRIKE',
    60: 'CR_READINESS',
    61: 'CR_SPEED',
    62: 'CR_LIFESTEAL',
    63: 'CR_AVOIDANCE',
    64: 'CR_STURDINESS',
    65: 'CR_UNUSED_7',
    66: 'CR_CLEAVE',
    67: 'CR_UNUSED_9',
    68: 'CR_UNUSED_10',
    69: 'CR_UNUSED_11',
    70: 'CR_UNUSED_12',
    71: 'AGI_STR_INT',
    72: 'AGI_STR',
    73: 'AGI_INT',
    74: 'STR_INT'
};

const spellEffectName = {
	1: 'INSTAKILL',
    2: 'SCHOOL_DAMAGE',
    3: 'DUMMY',
    4: 'PORTAL_TELEPORT',
    5: 'TELEPORT_UNITS_OLD',
    6: 'APPLY_AURA',
    7: 'ENVIRONMENTAL_DAMAGE',
    8: 'POWER_DRAIN',
    9: 'HEALTH_LEECH',
    10: 'HEAL',
    11: 'BIND',
    12: 'PORTAL',
    13: 'RITUAL_BASE',
    14: 'INCREASE_CURRENCY_CAP',
    15: 'RITUAL_ACTIVATE_PORTAL',
    16: 'QUEST_COMPLETE',
    17: 'WEAPON_DAMAGE_NOSCHOOL',
    18: 'RESURRECT',
    19: 'ADD_EXTRA_ATTACKS',
    20: 'DODGE',
    21: 'EVADE',
    22: 'PARRY',
    23: 'BLOCK',
    24: 'CREATE_ITEM',
    25: 'WEAPON',
    26: 'DEFENSE',
    27: 'PERSISTENT_AREA_AURA',
    28: 'SUMMON',
    29: 'LEAP',
    30: 'ENERGIZE',
    31: 'WEAPON_PERCENT_DAMAGE',
    32: 'TRIGGER_MISSILE',
    33: 'OPEN_LOCK',
    34: 'SUMMON_CHANGE_ITEM',
    35: 'APPLY_AREA_AURA_PARTY',
    36: 'LEARN_SPELL',
    37: 'SPELL_DEFENSE',
    38: 'DISPEL',
    39: 'LANGUAGE',
    40: 'DUAL_WIELD',
    41: 'JUMP',
    42: 'JUMP_DEST',
    43: 'TELEPORT_UNITS_FACE_CASTER',
    44: 'SKILL_STEP',
    45: 'PLAY_MOVIE',
    46: 'SPAWN',
    47: 'TRADE_SKILL',
    48: 'STEALTH',
    49: 'DETECT',
    50: 'TRANS_DOOR',
    51: 'FORCE_CRITICAL_HIT',
    52: 'SET_MAX_BATTLE_PET_COUNT',
    53: 'ENCHANT_ITEM',
    54: 'ENCHANT_ITEM_TEMPORARY',
    55: 'TAMECREATURE',
    56: 'SUMMON_PET',
    57: 'LEARN_PET_SPELL',
    58: 'WEAPON_DAMAGE',
    59: 'CREATE_RANDOM_ITEM',
    60: 'PROFICIENCY',
    61: 'SEND_EVENT',
    62: 'POWER_BURN',
    63: 'THREAT',
    64: 'TRIGGER_SPELL',
    65: 'APPLY_AREA_AURA_RAID',
    66: 'RECHARGE_ITEM',
    67: 'HEAL_MAX_HEALTH',
    68: 'INTERRUPT_CAST',
    69: 'DISTRACT',
    70: 'PULL',
    71: 'PICKPOCKET',
    72: 'ADD_FARSIGHT',
    73: 'UNTRAIN_TALENTS',
    74: 'APPLY_GLYPH',
    75: 'HEAL_MECHANICAL',
    76: 'SUMMON_OBJECT_WILD',
    77: 'SCRIPT_EFFECT',
    78: 'ATTACK',
    79: 'SANCTUARY',
    80: 'ADD_COMBO_POINTS',
    81: 'PUSH_ABILITY_TO_ACTION_BAR',
    82: 'BIND_SIGHT',
    83: 'DUEL',
    84: 'STUCK',
    85: 'SUMMON_PLAYER',
    86: 'ACTIVATE_OBJECT',
    87: 'GAMEOBJECT_DAMAGE',
    88: 'GAMEOBJECT_REPAIR',
    89: 'GAMEOBJECT_SET_DESTRUCTION_STATE',
    90: 'KILL_CREDIT',
    91: 'THREAT_ALL',
    92: 'ENCHANT_HELD_ITEM',
    93: 'FORCE_DESELECT',
    94: 'SELF_RESURRECT',
    95: 'SKINNING',
    96: 'CHARGE',
    97: 'CAST_BUTTON',
    98: 'KNOCK_BACK',
    99: 'DISENCHANT',
    100: 'INEBRIATE',
    101: 'FEED_PET',
    102: 'DISMISS_PET',
    103: 'REPUTATION',
    104: 'SUMMON_OBJECT_SLOT1',
    105: 'SURVEY',
    106: 'CHANGE_RAID_MARKER',
    107: 'SHOW_CORPSE_LOOT',
    108: 'DISPEL_MECHANIC',
    109: 'RESURRECT_PET',
    110: 'DESTROY_ALL_TOTEMS',
    111: 'DURABILITY_DAMAGE',
    112: 'UNK_112',
    113: 'UNK_113',
    114: 'ATTACK_ME',
    115: 'DURABILITY_DAMAGE_PCT',
    116: 'SKIN_PLAYER_CORPSE',
    117: 'SPIRIT_HEAL',
    118: 'SKILL',
    119: 'APPLY_AREA_AURA_PET',
    120: 'TELEPORT_GRAVEYARD',
    121: 'NORMALIZED_WEAPON_DMG',
    122: 'UNK_122',
    123: 'SEND_TAXI',
    124: 'PULL_TOWARDS',
    125: 'MODIFY_THREAT_PERCENT',
    126: 'STEAL_BENEFICIAL_BUFF',
    127: 'PROSPECTING',
    128: 'APPLY_AREA_AURA_FRIEND',
    129: 'APPLY_AREA_AURA_ENEMY',
    130: 'REDIRECT_THREAT',
    131: 'PLAY_SOUND',
    132: 'PLAY_MUSIC',
    133: 'UNLEARN_SPECIALIZATION',
    134: 'KILL_CREDIT2',
    135: 'CALL_PET',
    136: 'HEAL_PCT',
    137: 'ENERGIZE_PCT',
    138: 'LEAP_BACK',
    139: 'CLEAR_QUEST',
    140: 'FORCE_CAST',
    141: 'FORCE_CAST_WITH_VALUE',
    142: 'TRIGGER_SPELL_WITH_VALUE',
    143: 'APPLY_AREA_AURA_OWNER',
    144: 'KNOCK_BACK_DEST',
    145: 'PULL_TOWARDS_DEST',
    146: 'ACTIVATE_RUNE',
    147: 'QUEST_FAIL',
    148: 'TRIGGER_MISSILE_SPELL_WITH_VALUE',
    149: 'CHARGE_DEST',
    150: 'QUEST_START',
    151: 'TRIGGER_SPELL_2',
    152: 'SUMMON_RAF_FRIEND',
    153: 'CREATE_TAMED_PET',
    154: 'DISCOVER_TAXI',
    155: 'TITAN_GRIP',
    156: 'ENCHANT_ITEM_PRISMATIC',
    157: 'CREATE_LOOT',
    158: 'MILLING',
    159: 'ALLOW_RENAME_PET',
    160: 'FORCE_CAST_2',
    161: 'TALENT_SPEC_COUNT',
    162: 'TALENT_SPEC_SELECT',
    163: 'OBLITERATE_ITEM',
    164: 'REMOVE_AURA',
    165: 'DAMAGE_FROM_MAX_HEALTH_PCT',
    166: 'GIVE_CURRENCY',
    167: 'UPDATE_PLAYER_PHASE',
    168: 'ALLOW_CONTROL_PET',
    169: 'DESTROY_ITEM',
    170: 'UPDATE_ZONE_AURAS_AND_PHASES',
    171: 'UNK_171',
    172: 'RESURRECT_WITH_AURA',
    173: 'UNLOCK_GUILD_VAULT_TAB',
    174: 'APPLY_AURA_ON_PET',
    175: 'UNK_175',
    176: 'SANCTUARY_2',
    177: 'UNK_177',
    178: 'UNK_178',
    179: 'CREATE_AREATRIGGER',
    180: 'UPDATE_AREATRIGGER',
    181: 'REMOVE_TALENT',
    182: 'DESPAWN_AREATRIGGER',
    183: 'UNK_183',
    184: 'REPUTATION_2',
    185: 'UNK_185',
    186: 'UNK_186',
    187: 'RANDOMIZE_ARCHAEOLOGY_DIGSITES',
    188: 'UNK_188',
    189: 'LOOT',
    190: 'UNK_190',
    191: 'TELEPORT_TO_DIGSITE',
    192: 'UNCAGE_BATTLEPET',
    193: 'START_PET_BATTLE',
    194: 'UNK_194',
    195: 'UNK_195',
    196: 'UNK_196',
    197: 'UNK_197',
    198: 'PLAY_SCENE',
    199: 'UNK_199',
    200: 'HEAL_BATTLEPET_PCT',
    201: 'ENABLE_BATTLE_PETS',
    202: 'UNK_202',
    203: 'UNK_203',
    204: 'CHANGE_BATTLEPET_QUALITY',
    205: 'LAUNCH_QUEST_CHOICE',
    206: 'ALTER_ITEM',
    207: 'LAUNCH_QUEST_TASK',
    208: 'UNK_208',
    209: 'UNK_209',
    210: 'LEARN_GARRISON_BUILDING',
    211: 'LEARN_GARRISON_SPECIALIZATION',
    212: 'UNK_212',
    213: 'UNK_213',
    214: 'CREATE_GARRISON',
    215: 'UPGRADE_CHARACTER_SPELLS',
    216: 'CREATE_SHIPMENT',
    217: 'UPGRADE_GARRISON',
    218: 'UNK_218',
    219: 'CREATE_CONVERSATION',
    220: 'ADD_GARRISON_FOLLOWER',
    221: 'UNK_221',
    222: 'CREATE_HEIRLOOM_ITEM',
    223: 'CHANGE_ITEM_BONUSES',
    224: 'ACTIVATE_GARRISON_BUILDING',
    225: 'GRANT_BATTLEPET_LEVEL',
    226: 'UNK_226',
    227: 'TELEPORT_TO_LFG_DUNGEON',
    228: 'UNK_228',
    229: 'SET_FOLLOWER_QUALITY',
    230: 'INCREASE_FOLLOWER_ITEM_LEVEL',
    231: 'INCREASE_FOLLOWER_EXPERIENCE',
    232: 'REMOVE_PHASE',
    233: 'RANDOMIZE_FOLLOWER_ABILITIES',
    234: 'UNK_234',
    235: 'UNK_235',
    236: 'GIVE_EXPERIENCE',
    237: 'GIVE_RESTED_EXPERIENCE_BONUS',
    238: 'INCREASE_SKILL',
    239: 'END_GARRISON_BUILDING_CONSTRUCTION',
    240: 'GIVE_ARTIFACT_POWER',
    241: 'UNK_241',
    242: 'GIVE_ARTIFACT_POWER_NO_BONUS',
    243: 'APPLY_ENCHANT_ILLUSION',
    244: 'LEARN_FOLLOWER_ABILITY',
    245: 'UPGRADE_HEIRLOOM',
    246: 'FINISH_GARRISON_MISSION',
    247: 'ADD_GARRISON_MISSION',
    248: 'FINISH_SHIPMENT',
    249: 'FORCE_EQUIP_ITEM',
    250: 'TAKE_SCREENSHOT',
    251: 'SET_GARRISON_CACHE_SIZE',
    252: 'TELEPORT_UNITS',
    253: 'GIVE_HONOR',
    254: 'UNK_254',
    255: 'LEARN_TRANSMOG_SET',
    256: 'UNK_256',
    257: 'UNK_257',
    258: 'MODIFY_KEYSTONE',
    259: 'RESPEC_AZERITE_EMPOWERED_ITEM',
    260: 'SUMMON_STABLED_PET',
    261: 'SCRAP_ITEM',
    262: 'UNK_262',
    263: 'REPAIR_ITEM',
    264: 'REMOVE_GEM',
    265: 'LEARN_AZERITE_ESSENCE_POWER',
    266: 'UNK_266',
    267: 'UNK_267',
    268: 'APPLY_MOUNT_EQUIPMENT',
    269: 'UPGRADE_ITEM',
    270: 'UNK_270',
    271: 'APPLY_AREA_AURA_PARTY_NONRANDOM'
};

const charSectionType = {
	0: 'Skin',
	1: 'Face',
	2: 'FacialHair',
	3: 'Hair',
	4: 'Underwear',
	5: 'HDSkin',
	6: 'HDFace',
	7: 'HDFacialHair',
	8: 'HDHair',
	9: 'HDUnderwear',
	10: 'Custom1',
	11: 'HDCustom1',
	12: 'Custom2',
	13: 'HDCustom2',
	14: 'Custom3',
	15: 'HDCustom3'
}

const charSex = {
	0: 'Male',
	1: 'Female'
}

const uiMapType = {
	0: 'Cosmic',
	1: 'World',
	2: 'Continent',
	3: 'Zone',
	4: 'Dungeon',
	5: 'Micro',
	6: 'Orphan'
}

// Regular enums
let enumMap = new Map();
enumMap.set("map.ExpansionID", expansionLevels);
enumMap.set("map.InstanceType", mapTypes);
enumMap.set("difficulty.InstanceType", mapTypes);
enumMap.set("playercondition.MinReputation[0]", reputationLevels);
enumMap.set("itembonus.Type", itemBonusTypes);
enumMap.set("criteriatree.Operator", criteriaTreeOperator);
enumMap.set("modifiertree.Operator", modifierTreeOperator);
enumMap.set("modifiertree.Type", criteriaAdditionalCondition);
enumMap.set("spelleffect.Effect", spellEffectName);
enumMap.set("charsections.BaseSection", charSectionType);
enumMap.set("charsections.SexID", charSex);
enumMap.set("charsectioncondition.BaseSection", charSectionType);
enumMap.set("charsectioncondition.Sex", charSex);
enumMap.set("uimap.Type", uiMapType);
// Conditional enums
let conditionalEnums = new Map();
conditionalEnums.set("itembonus.Value[0]",
	[
		['itembonus.Type=2', itemStatType],
	]
);

// Conditional FKs (move to sep file?)
let conditionalFKs = new Map();
conditionalFKs.set("itembonus.Value[0]",
	[
		['itembonus.Type=19','azeritetierunlockset::ID'],
		['itembonus.Type=23','itemeffect::ID']
	]
);

conditionalFKs.set("spelleffect.EffectMiscValue[0]",
	[
		['spelleffect.Effect=90','creature::ID'],
		['spelleffect.Effect=131','soundkit::ID'],
		['spelleffect.Effect=132','soundkit::ID'],
	]
);